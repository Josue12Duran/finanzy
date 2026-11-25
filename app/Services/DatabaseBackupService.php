<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;

class DatabaseBackupService
{
    public function create(): string
    {
        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory, 0700);

        $path = $directory.DIRECTORY_SEPARATOR
            .'finanzy-backup-'.now()->format('Ymd-His').'.sql';
        $stream = fopen($path, 'xb');

        if (! is_resource($stream)) {
            throw new RuntimeException('No se pudo crear el archivo temporal de la copia de seguridad.');
        }

        $complete = false;

        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();

            match ($connection->getDriverName()) {
                'mysql', 'mariadb' => $this->writeMySqlBackup($stream, $pdo),
                'sqlite' => $this->writeSqliteBackup($stream, $pdo),
                default => throw new RuntimeException('El motor de base de datos no admite copias de seguridad SQL.'),
            };

            if (! fflush($stream)) {
                throw new RuntimeException('No se pudo completar el archivo de la copia de seguridad.');
            }

            $complete = true;

            return $path;
        } finally {
            fclose($stream);

            if (! $complete && File::exists($path)) {
                File::delete($path);
            }
        }
    }

    /**
     * @param  resource  $stream
     */
    private function writeMySqlBackup(mixed $stream, PDO $pdo): void
    {
        $this->writeLine($stream, '-- Copia de seguridad Finanzy');
        $this->writeLine($stream, 'SET FOREIGN_KEY_CHECKS=0;');
        $this->writeLine($stream, 'START TRANSACTION;');

        $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');

        try {
            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $quotedTable = $this->quoteIdentifier($table, '`');
                $createTable = $pdo->query('SHOW CREATE TABLE '.$quotedTable)->fetch(PDO::FETCH_NUM)[1];

                $this->writeLine($stream, 'DROP TABLE IF EXISTS '.$quotedTable.';');
                $this->writeLine($stream, $createTable.';');

                $rows = $pdo->query('SELECT * FROM '.$quotedTable);

                while (($row = $rows->fetch(PDO::FETCH_ASSOC)) !== false) {
                    $columns = array_map(
                        fn (string $column): string => $this->quoteIdentifier($column, '`'),
                        array_keys($row),
                    );
                    $values = array_map(fn (mixed $value): string => $this->quoteValue($pdo, $value), array_values($row));

                    $this->writeLine(
                        $stream,
                        'INSERT INTO '.$quotedTable.' ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).');',
                    );
                }
            }

            $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($views as $view) {
                $quotedView = $this->quoteIdentifier($view, '`');
                $createView = $pdo->query('SHOW CREATE VIEW '.$quotedView)->fetch(PDO::FETCH_NUM)[1];

                $this->writeLine($stream, 'DROP VIEW IF EXISTS '.$quotedView.';');
                $this->writeLine($stream, $createView.';');
            }

            $this->writeLine($stream, 'COMMIT;');
            $this->writeLine($stream, 'SET FOREIGN_KEY_CHECKS=1;');
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }

    /**
     * @param  resource  $stream
     */
    private function writeSqliteBackup(mixed $stream, PDO $pdo): void
    {
        $schema = $pdo->query(
            "SELECT type, name, sql FROM sqlite_master
             WHERE sql IS NOT NULL AND name NOT LIKE 'sqlite_%'
             ORDER BY CASE type
                 WHEN 'table' THEN 0
                 WHEN 'view' THEN 1
                 WHEN 'index' THEN 2
                 WHEN 'trigger' THEN 3
                 ELSE 4
             END, name",
        )->fetchAll(PDO::FETCH_ASSOC);
        $tables = array_filter($schema, fn (array $object): bool => $object['type'] === 'table');

        $this->writeLine($stream, '-- Copia de seguridad Finanzy');
        $this->writeLine($stream, 'PRAGMA foreign_keys=OFF;');
        $this->writeLine($stream, 'BEGIN TRANSACTION;');

        foreach ($schema as $object) {
            if ($object['type'] === 'view') {
                $this->writeLine($stream, 'DROP VIEW IF EXISTS '.$this->quoteIdentifier($object['name'], '"').';');
            }
        }

        foreach ($tables as $table) {
            $this->writeLine($stream, 'DROP TABLE IF EXISTS '.$this->quoteIdentifier($table['name'], '"').';');
        }

        foreach ($tables as $table) {
            $this->writeLine($stream, $table['sql'].';');
        }

        foreach ($tables as $table) {
            $quotedTable = $this->quoteIdentifier($table['name'], '"');
            $rows = $pdo->query('SELECT * FROM '.$quotedTable);

            while (($row = $rows->fetch(PDO::FETCH_ASSOC)) !== false) {
                $columns = array_map(
                    fn (string $column): string => $this->quoteIdentifier($column, '"'),
                    array_keys($row),
                );
                $values = array_map(fn (mixed $value): string => $this->quoteValue($pdo, $value), array_values($row));

                $this->writeLine(
                    $stream,
                    'INSERT INTO '.$quotedTable.' ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).');',
                );
            }
        }

        $hasSequence = $pdo->query(
            "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'sqlite_sequence'",
        )->fetchColumn();

        if ($hasSequence !== false) {
            $sequenceRows = $pdo->query('SELECT name, seq FROM sqlite_sequence')->fetchAll(PDO::FETCH_ASSOC);
            $this->writeLine($stream, 'DELETE FROM sqlite_sequence;');

            foreach ($sequenceRows as $sequence) {
                $this->writeLine(
                    $stream,
                    'INSERT INTO sqlite_sequence (name, seq) VALUES ('
                        .$this->quoteValue($pdo, $sequence['name']).', '
                        .$this->quoteValue($pdo, $sequence['seq']).');',
                );
            }
        }

        foreach ($schema as $object) {
            if (in_array($object['type'], ['view', 'index', 'trigger'], true)) {
                $this->writeLine($stream, $object['sql'].';');
            }
        }

        $this->writeLine($stream, 'COMMIT;');
        $this->writeLine($stream, 'PRAGMA foreign_keys=ON;');
    }

    private function quoteIdentifier(string $identifier, string $quote): string
    {
        return $quote.str_replace($quote, $quote.$quote, $identifier).$quote;
    }

    private function quoteValue(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);

            if ($value === false) {
                throw new RuntimeException('No se pudo leer un valor de la base de datos.');
            }
        }

        $quotedValue = $pdo->quote((string) $value);

        if ($quotedValue === false) {
            throw new RuntimeException('No se pudo codificar un valor de la base de datos.');
        }

        return $quotedValue;
    }

    /**
     * @param  resource  $stream
     */
    private function writeLine(mixed $stream, string $line): void
    {
        $line .= "\n";
        $written = 0;
        $length = strlen($line);

        while ($written < $length) {
            $bytes = fwrite($stream, substr($line, $written));

            if ($bytes === false || $bytes === 0) {
                throw new RuntimeException('No se pudo escribir el archivo de la copia de seguridad.');
            }

            $written += $bytes;
        }
    }
}
