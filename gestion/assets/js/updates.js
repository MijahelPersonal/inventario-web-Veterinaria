// Presenta avisos en todas las pantallas sin exponer Node ni credenciales al navegador.
export function mountUpdates() {
    const api = window.struchDesktop;
    if (!api) return;
    const status = document.getElementById('update-status');
    const notes = document.getElementById('update-notes');
    const check = document.getElementById('check-update');
    const download = document.getElementById('download-update');
    const install = document.getElementById('install-update');
    const banner = document.createElement('div');
    banner.className = 'alert alert-info';
    banner.setAttribute('role', 'status');
    banner.hidden = true;
    document.querySelector('.workspace-content')?.prepend(banner);
    const render = (state) => {
        const messages = {
            idle: 'Puedes buscar nuevas versiones de escritorio.',
            development:
                'Modo desarrollo: las actualizaciones funcionan desde el instalador publicado.',
            checking: 'Buscando actualizaciones…',
            current: 'Tu aplicación de escritorio está actualizada.',
            available: `Disponible la versión ${state.version}. Puedes descargarla desde Configuración.`,
            downloading: `Descargando actualización: ${state.progress || 0} %.`,
            downloaded:
                'Actualización lista. Reinicia desde Configuración cuando hayas guardado tu trabajo.',
            error: 'No se pudo consultar o descargar la actualización. Comprueba tu conexión e inténtalo de nuevo.',
        };
        const message = messages[state.status] || '';
        if (status) status.textContent = `Electron v${state.installed} · ${message}`;
        if (notes) notes.textContent = state.notes || '';
        if (check) check.hidden = !['idle', 'current', 'error'].includes(state.status);
        if (download) download.hidden = state.status !== 'available';
        if (install) install.hidden = state.status !== 'downloaded';
        banner.hidden = !['available', 'downloading', 'downloaded'].includes(state.status);
        banner.textContent = message;
    };
    const run = async (operation) => {
        try {
            render(await operation());
        } catch {
            if (status) status.textContent = 'No se pudo completar la actualización.';
        }
    };
    check?.addEventListener('click', () => run(() => api.check()));
    download?.addEventListener('click', () => run(() => api.download()));
    install?.addEventListener('click', () => run(() => api.install()));
    api.subscribe(render);
    run(() => api.state());
}
