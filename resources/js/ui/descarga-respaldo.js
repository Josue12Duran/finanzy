const COOKIE_NAME = 'backup_download_started';

export function registrarDescargaRespaldo(documento = document, ventana = window) {
    const formulario = documento.getElementById('backup-download-form');

    if (!formulario) {
        return;
    }

    const boton = formulario.querySelector('[type="submit"]');
    const token = formulario.querySelector('[name="download_token"]');
    const iframe = documento.getElementById('backup-download-frame');
    const overlay = documento.getElementById('backup-loading-overlay');
    const error = documento.getElementById('backup-download-error');
    const appShell = documento.getElementById('app-shell');
    let temporizador = null;
    let solicitudPendiente = false;
    let manejarCargaIframe = null;

    const valorCookie = () => {
        const prefijo = `${COOKIE_NAME}=`;
        const cookie = documento.cookie.split('; ').find((valor) => valor.startsWith(prefijo));

        return cookie ? decodeURIComponent(cookie.slice(prefijo.length)) : null;
    };

    const borrarCookie = () => {
        const secure = ventana.location.protocol === 'https:' ? '; Secure' : '';

        documento.cookie = `${COOKIE_NAME}=; Max-Age=0; Path=/; SameSite=Lax${secure}`;
    };

    const bloquearNavegacion = (evento) => {
        evento.preventDefault();
        evento.returnValue = '';
    };

    const finalizarSolicitud = (fallida = false) => {
        if (!solicitudPendiente) {
            return;
        }

        solicitudPendiente = false;
        ventana.clearInterval(temporizador);
        ventana.removeEventListener('beforeunload', bloquearNavegacion);
        iframe.removeEventListener('load', manejarCargaIframe);
        overlay.hidden = true;
        appShell.inert = false;
        error.hidden = !fallida;
        boton.dispatchEvent(new ventana.Event('finanzy:loading-complete', { bubbles: true }));
        boton.focus();
    };

    const comprobarDescarga = () => {
        if (valorCookie() === token.value) {
            borrarCookie();
            finalizarSolicitud();
        }
    };

    formulario.addEventListener('submit', (evento) => {
        if (evento.defaultPrevented || solicitudPendiente) {
            if (solicitudPendiente) {
                evento.preventDefault();
            }

            return;
        }

        solicitudPendiente = true;
        borrarCookie();
        error.hidden = true;
        overlay.hidden = false;
        documento.body.append(overlay);
        appShell.inert = true;
        overlay.focus();
        ventana.addEventListener('beforeunload', bloquearNavegacion);

        manejarCargaIframe = () => {
            if (valorCookie() === token.value) {
                comprobarDescarga();

                return;
            }

            try {
                if (iframe.contentWindow.location.href !== 'about:blank') {
                    finalizarSolicitud(true);
                }
            } catch {
                finalizarSolicitud(true);
            }
        };

        iframe.addEventListener('load', manejarCargaIframe);
        temporizador = ventana.setInterval(comprobarDescarga, 200);
    });
}
