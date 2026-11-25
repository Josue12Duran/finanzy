import assert from 'node:assert/strict';
import { describe, test } from 'node:test';
import { registrarDescargaRespaldo } from '../../resources/js/ui/descarga-respaldo.js';
import { registrarEstadoDeCarga } from '../../resources/js/ui/estado-carga.js';

const TOKEN = '11111111-1111-4111-8111-111111111111';

function crearElemento(tagName, document) {
    const listeners = new Map();
    const attributes = new Map();
    const classes = new Set();

    return {
        tagName,
        dataset: { loadingText: 'Generando copia…' },
        innerHTML: 'Generar copia',
        disabled: false,
        hidden: true,
        inert: false,
        classList: {
            add: (value) => classes.add(value),
            remove: (value) => classes.delete(value),
            contains: (value) => classes.has(value),
        },
        addEventListener(type, listener) {
            listeners.set(type, listener);
        },
        removeEventListener(type) {
            listeners.delete(type);
        },
        dispatchEvent(event) {
            event.target = this;
            if (event.bubbles) {
                document.dispatchEvent(event);
            }

            return true;
        },
        emit(type, event) {
            listeners.get(type)?.(event);
        },
        setAttribute(name, value) {
            attributes.set(name, value);
        },
        removeAttribute(name) {
            attributes.delete(name);
        },
        getAttribute(name) {
            return attributes.get(name) ?? null;
        },
        replaceChildren(...children) {
            this.innerHTML = children.map((child) => child.textContent ?? '').join('');
        },
        focus() {},
    };
}

function crearPagina() {
    const listeners = new Map();
    const intervalos = new Map();
    let siguienteIntervalo = 0;
    const documento = {
        cookie: '',
        body: { append() {} },
        createElement: (tagName) => ({ tagName, setAttribute() {} }),
        addEventListener(type, listener) {
            const eventListeners = listeners.get(type) ?? [];
            eventListeners.push(listener);
            listeners.set(type, eventListeners);
        },
        dispatchEvent(event) {
            for (const listener of listeners.get(event.type) ?? []) {
                listener(event);
            }
        },
        getElementById(id) {
            return elements[id] ?? null;
        },
    };
    const formulario = crearElemento('FORM', documento);
    const boton = crearElemento('BUTTON', documento);
    const token = { value: TOKEN };
    const iframe = crearElemento('IFRAME', documento);
    iframe.contentWindow = { location: { href: 'about:blank' } };
    const overlay = crearElemento('DIV', documento);
    const error = crearElemento('DIV', documento);
    const appShell = crearElemento('DIV', documento);
    const elements = {
        'backup-download-form': formulario,
        'backup-download-frame': iframe,
        'backup-loading-overlay': overlay,
        'backup-download-error': error,
        'app-shell': appShell,
    };

    formulario.querySelector = (selector) => {
        if (selector === '[type="submit"]' || selector === '[type="submit"][data-loading-text]') {
            return boton;
        }

        if (selector === '[name="download_token"]') {
            return token;
        }

        return null;
    };

    boton.dispatchEvent = (event) => {
        event.target = boton;
        documento.dispatchEvent(event);

        return true;
    };

    const ventana = {
        location: { protocol: 'https:' },
        Event: class {
            constructor(type, options) {
                this.type = type;
                this.bubbles = options.bubbles;
            }
        },
        addEventListener(type, listener) {
            listeners.set(`window:${type}`, [listener]);
        },
        removeEventListener(type) {
            listeners.delete(`window:${type}`);
        },
        setInterval(callback) {
            const id = ++siguienteIntervalo;
            intervalos.set(id, callback);

            return id;
        },
        clearInterval(id) {
            intervalos.delete(id);
        },
    };

    return {
        documento,
        ventana,
        formulario,
        boton,
        iframe,
        overlay,
        error,
        appShell,
        intervalos,
    };
}

function enviarFormulario(pagina) {
    const evento = {
        type: 'submit',
        target: pagina.formulario,
        defaultPrevented: false,
        preventDefault() {
            this.defaultPrevented = true;
        },
    };

    pagina.formulario.emit('submit', evento);
    pagina.documento.dispatchEvent(evento);

    return evento;
}

describe('registrarDescargaRespaldo', () => {
    test('bloquea la interfaz hasta que inicia la descarga y luego la restablece', () => {
        const pagina = crearPagina();
        registrarEstadoDeCarga(pagina.documento, pagina.ventana);
        registrarDescargaRespaldo(pagina.documento, pagina.ventana);

        enviarFormulario(pagina);

        assert.equal(pagina.boton.disabled, true);
        assert.equal(pagina.overlay.hidden, false);
        assert.equal(pagina.appShell.inert, true);

        pagina.documento.cookie = `backup_download_started=${TOKEN}`;
        [...pagina.intervalos.values()][0]();

        assert.equal(pagina.overlay.hidden, true);
        assert.equal(pagina.appShell.inert, false);
        assert.equal(pagina.boton.disabled, false);
        assert.equal(pagina.error.hidden, true);
        assert.equal(pagina.intervalos.size, 0);
    });

    test('restablece la interfaz y muestra un error si falla la descarga', () => {
        const pagina = crearPagina();
        registrarEstadoDeCarga(pagina.documento, pagina.ventana);
        registrarDescargaRespaldo(pagina.documento, pagina.ventana);

        enviarFormulario(pagina);
        pagina.iframe.contentWindow.location.href = '/copias-seguridad/descargar';
        pagina.iframe.emit('load');

        assert.equal(pagina.overlay.hidden, true);
        assert.equal(pagina.appShell.inert, false);
        assert.equal(pagina.boton.disabled, false);
        assert.equal(pagina.error.hidden, false);
        assert.equal(pagina.intervalos.size, 0);
    });

    test('cancela un segundo envío mientras el respaldo sigue generándose', () => {
        const pagina = crearPagina();
        registrarEstadoDeCarga(pagina.documento, pagina.ventana);
        registrarDescargaRespaldo(pagina.documento, pagina.ventana);

        enviarFormulario(pagina);
        const segundoEnvio = enviarFormulario(pagina);

        assert.equal(segundoEnvio.defaultPrevented, true);
        assert.equal(pagina.intervalos.size, 1);
    });
});
