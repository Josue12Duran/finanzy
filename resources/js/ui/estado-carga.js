/**
 * Indicador de carga y protección contra dobles envíos.
 *
 * - Formularios: el botón de envío con data-loading-text se desactiva y muestra un
 *   indicador mientras se carga la página siguiente.
 * - Enlaces con data-loading-text (p. ej. la descarga del PDF): se desactivan durante
 *   unos segundos, porque una descarga no cambia de página y el navegador no avisa
 *   cuando termina.
 * - Al volver con el botón «Atrás» (caché del navegador) se restablece el estado.
 */

export const DURACION_ENLACE_MS = 8000;

export function estaCargando(elemento) {
    return elemento.dataset.cargando === 'true';
}

/**
 * Muestra el indicador y desactiva el elemento. Devuelve false si ya estaba cargando.
 */
export function activarCarga(elemento, documento = document) {
    if (estaCargando(elemento)) {
        return false;
    }

    elemento.dataset.cargando = 'true';
    elemento.dataset.contenidoOriginal = elemento.innerHTML;

    const indicador = documento.createElement('span');
    indicador.className = 'spinner-border spinner-border-sm me-1';
    indicador.setAttribute('aria-hidden', 'true');

    const texto = documento.createElement('span');
    texto.setAttribute('role', 'status');
    texto.textContent = elemento.dataset.loadingText;

    elemento.replaceChildren(indicador, texto);
    elemento.setAttribute('aria-disabled', 'true');
    elemento.classList.add('disabled');

    if (elemento.tagName === 'BUTTON') {
        elemento.disabled = true;
    }

    return true;
}

export function desactivarCarga(elemento) {
    if (!estaCargando(elemento)) {
        return;
    }

    elemento.innerHTML = elemento.dataset.contenidoOriginal;
    delete elemento.dataset.cargando;
    delete elemento.dataset.contenidoOriginal;
    elemento.removeAttribute('aria-disabled');
    elemento.classList.remove('disabled');

    if (elemento.tagName === 'BUTTON') {
        elemento.disabled = false;
    }
}

export function registrarEstadoDeCarga(documento = document, ventana = window) {
    documento.addEventListener('submit', (evento) => {
        const boton = evento.target.querySelector('[type="submit"][data-loading-text]');

        // Un envío cancelado (p. ej. por las validaciones del cliente) no muestra carga.
        if (!boton || evento.defaultPrevented) {
            return;
        }

        if (!activarCarga(boton, documento)) {
            evento.preventDefault();
        }
    });

    documento.addEventListener('click', (evento) => {
        const enlace = evento.target.closest?.('a[data-loading-text]');

        if (!enlace) {
            return;
        }

        if (!activarCarga(enlace, documento)) {
            evento.preventDefault();

            return;
        }

        ventana.setTimeout(() => desactivarCarga(enlace), DURACION_ENLACE_MS);
    });

    documento.addEventListener('finanzy:loading-complete', (evento) => {
        desactivarCarga(evento.target);
    });

    ventana.addEventListener('pageshow', (evento) => {
        if (evento.persisted) {
            documento.querySelectorAll('[data-cargando="true"]').forEach((elemento) => desactivarCarga(elemento));
        }
    });
}
