const { app, BrowserWindow, dialog } = require('electron');
let backend;
try {
    backend = new URL(process.env.STRUCH_VET_URL || 'http://127.0.0.1:8080');
    const local = ['127.0.0.1', 'localhost', '[::1]'].includes(backend.hostname);
    if (backend.protocol !== 'https:' && !(local && backend.protocol === 'http:')) {
        throw new Error('El backend remoto requiere HTTPS.');
    }
    if (backend.username || backend.password)
        throw new Error('La URL no debe incluir credenciales.');
} catch (error) {
    console.error(error.message);
    app.exit(1);
}

app.whenReady().then(() => {
    const createWindow = () => {
        const window = new BrowserWindow({
            width: 1280,
            height: 800,
            minWidth: 900,
            minHeight: 600,
            title: 'STRUCH VET',
            webPreferences: { nodeIntegration: false, contextIsolation: true, sandbox: true },
        });
        window.webContents.setWindowOpenHandler(() => ({ action: 'deny' }));
        window.webContents.on('will-navigate', (event, url) => {
            if (new URL(url).origin !== backend.origin) event.preventDefault();
        });
        window.webContents.session.setPermissionRequestHandler((_contents, _permission, callback) =>
            callback(false),
        );
        window.loadURL(backend.href).catch(() => {
            dialog.showErrorBox(
                'Backend no disponible',
                'Inicia el servidor PHP y comprueba la dirección configurada.',
            );
        });
    };
    createWindow();
    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) createWindow();
    });
});
app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit();
});
