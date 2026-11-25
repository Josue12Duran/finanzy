/**
 * Pruebas del indicador de carga y de la protección contra dobles envíos.
 * Se ejecutan con el ejecutor nativo de Node, con elementos simulados:
 *
 *     docker compose run --rm frontend node --test tests/js/estado-carga.test.js
 */

import assert from 'node:assert/strict';
import { describe, test } from 'node:test';
import { activarCarga, desactivarCarga, DURACION_ENLACE_MS, registrarEstadoDeCarga } from '../../resources/js/ui/estado-carga.js';

function elementoSimulado(tagName, { loadingText = 'Generando…', innerHTML = 'Generar reporte' } = {}) {
    const atributos = new Map();
    const clases = new Set();

    return {
        tagName,
        innerHTML,
        disabled: false,
        dataset: { loadingText },
        hijos: [],
        classList: { add: (clase) => clases.add(clase), remove: (clase) => clases.delete(clase), contains: (clase) => clases.has(clase) },
        setAttribute: (nombre, valor) => atributos.set(nombre, valor),
        removeAttribute: (nombre) => atributos.delete(nombre),
        getAttribute: (nombre) => atributos.get(nombre) ?? null,
        replaceChildren(...hijos) {
            this.hijos = hijos;
            this.innerHTML = hijos.map((hijo) => hijo.textContent ?? '').join('');
        },
    };
}

function documentoSimulado(elementosCargando = []) {
    const oyentes = {};

    return {
        oyentes,
        createElement: (tagName) => elementoSimulado(tagName.toUpperCase(), { loadingText: undefined, innerHTML: '' }),
        addEventListener: (tipo, oyente) => { oyentes[tipo] = oyente; },
        querySelectorAll: () => elementosCargando,
    };
}

function ventanaSimulada() {
    const ventana = { temporizadores: [], oyentes: {} };
    ventana.setTimeout = (funcion, retraso) => ventana.temporizadores.push({ funcion, retraso });
    ventana.addEventListener = (tipo, oyente) => { ventana.oyentes[tipo] = oyente; };

    return ventana;
}

function eventoSimulado(target) {
    return { target, cancelado: false, preventDefault() { this.cancelado = true; } };
}

describe('activarCarga y desactivarCarga', () => {
    test('un botón muestra el texto de carga y queda desactivado', () => {
        const boton = elementoSimulado('BUTTON', { loadingText: 'Generando reporte…' });

        assert.equal(activarCarga(boton, documentoSimulado()), true);

        assert.equal(boton.disabled, true);
        assert.equal(boton.getAttribute('aria-disabled'), 'true');
        assert.equal(boton.classList.contains('disabled'), true);
        assert.equal(boton.hijos[1].textContent, 'Generando reporte…');
        assert.equal(boton.hijos[1].getAttribute('role'), 'status');
    });

    test('no se activa dos veces', () => {
        const boton = elementoSimulado('BUTTON');
        activarCarga(boton, documentoSimulado());

        assert.equal(activarCarga(boton, documentoSimulado()), false);
    });

    test('al desactivar se recupera el contenido y el estado original', () => {
        const enlace = elementoSimulado('A', { innerHTML: '<i class="bi bi-file-earmark-pdf"></i> Exportar PDF' });
        activarCarga(enlace, documentoSimulado());

        desactivarCarga(enlace);

        assert.equal(enlace.innerHTML, '<i class="bi bi-file-earmark-pdf"></i> Exportar PDF');
        assert.equal(enlace.getAttribute('aria-disabled'), null);
        assert.equal(enlace.classList.contains('disabled'), false);
        assert.equal(enlace.dataset.cargando, undefined);
    });
});

describe('registrarEstadoDeCarga', () => {
    test('un segundo envío del formulario se cancela', () => {
        const documento = documentoSimulado();
        registrarEstadoDeCarga(documento, ventanaSimulada());
        const boton = elementoSimulado('BUTTON');
        const formulario = { querySelector: () => boton };

        const primero = eventoSimulado(formulario);
        const segundo = eventoSimulado(formulario);
        documento.oyentes.submit(primero);
        documento.oyentes.submit(segundo);

        assert.equal(primero.cancelado, false);
        assert.equal(segundo.cancelado, true);
        assert.equal(boton.disabled, true);
    });

    test('un envío cancelado por las validaciones no muestra el indicador de carga', () => {
        const documento = documentoSimulado();
        registrarEstadoDeCarga(documento, ventanaSimulada());
        const boton = elementoSimulado('BUTTON');
        const evento = { ...eventoSimulado({ querySelector: () => boton }), defaultPrevented: true };

        documento.oyentes.submit(evento);

        assert.equal(boton.disabled, false);
        assert.equal(boton.dataset.cargando, undefined);
    });

    test('los formularios sin texto de carga no se modifican', () => {
        const documento = documentoSimulado();
        registrarEstadoDeCarga(documento, ventanaSimulada());
        const evento = eventoSimulado({ querySelector: () => null });

        documento.oyentes.submit(evento);

        assert.equal(evento.cancelado, false);
    });

    test('el evento de fin de carga restablece el botón de un formulario de descarga', () => {
        const documento = documentoSimulado();
        registrarEstadoDeCarga(documento, ventanaSimulada());
        const boton = elementoSimulado('BUTTON', { loadingText: 'Generando copia…' });
        activarCarga(boton, documento);

        documento.oyentes['finanzy:loading-complete']({ target: boton });

        assert.equal(boton.disabled, false);
        assert.equal(boton.innerHTML, 'Generar reporte');
        assert.equal(boton.dataset.cargando, undefined);
    });

    test('un enlace de descarga se bloquea y se restablece tras unos segundos', () => {
        const documento = documentoSimulado();
        const ventana = ventanaSimulada();
        registrarEstadoDeCarga(documento, ventana);
        const enlace = elementoSimulado('A', { innerHTML: 'Exportar PDF' });
        const objetivo = { closest: () => enlace };

        const primero = eventoSimulado(objetivo);
        const segundo = eventoSimulado(objetivo);
        documento.oyentes.click(primero);
        documento.oyentes.click(segundo);

        assert.equal(primero.cancelado, false);
        assert.equal(segundo.cancelado, true);
        assert.equal(ventana.temporizadores.length, 1);
        assert.equal(ventana.temporizadores[0].retraso, DURACION_ENLACE_MS);

        ventana.temporizadores[0].funcion();

        assert.equal(enlace.innerHTML, 'Exportar PDF');
        assert.equal(enlace.dataset.cargando, undefined);
    });

    test('al volver con «Atrás» desde la caché se restablecen los botones', () => {
        const boton = elementoSimulado('BUTTON');
        activarCarga(boton, documentoSimulado());
        const ventana = ventanaSimulada();
        registrarEstadoDeCarga(documentoSimulado([boton]), ventana);

        ventana.oyentes.pageshow({ persisted: true });

        assert.equal(boton.disabled, false);
        assert.equal(boton.innerHTML, 'Generar reporte');
    });
});
