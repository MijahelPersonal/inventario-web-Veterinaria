const { app, BrowserWindow, dialog, ipcMain } = require('electron');
const path = require('node:path');
const { autoUpdater } = require('electron-updater');
const { DesktopUpdates } = require('./updates');
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
    const updates = new DesktopUpdates(autoUpdater, app.getVersion(), app.isPackaged);
    // Rechaza mensajes de subventanas o páginas ajenas al backend configurado.
    const trusted = (event) => {
        const owner = BrowserWindow.fromWebContents(event.sender);
        if (
            !owner ||
            event.senderFrame !== event.sender.mainFrame ||
            new URL(event.senderFrame.url).origin !== backend.origin
        ) {
            throw new Error('Solicitud de actualización no autorizada.');
        }
        return owner;
    };
    ipcMain.handle('updates:state', (event) => {
        trusted(event);
        return updates.state;
    });
    ipcMain.handle('updates:check', (event) => {
        trusted(event);
        return updates.check();
    });
    ipcMain.handle('updates:download', (event) => {
        trusted(event);
        return updates.download();
    });
    ipcMain.handle('updates:install', async (event) => {
        const owner = trusted(event);
        if (updates.state.status !== 'downloaded') return updates.state;
        const result = await dialog.showMessageBox(owner, {
            type: 'question',
            title: 'Actualizar STRUCH VET',
            message: 'Guarda tu trabajo antes de reiniciar e instalar la actualización.',
            buttons: ['Ahora no', 'Reiniciar e instalar'],
            defaultId: 0,
            cancelId: 0,
        });
        if (result.response === 1) updates.install();
        return updates.state;
    });
    updates.on('state', (state) => {
        for (const window of BrowserWindow.getAllWindows()) {
            if (
                !window.isDestroyed() &&
                new URL(window.webContents.getURL() || backend.href).origin === backend.origin
            )
                window.webContents.send('updates:state', state);
        }
    });
    const createWindow = () => {
        const window = new BrowserWindow({
            width: 1280,
            height: 800,
            minWidth: 900,
            minHeight: 600,
            title: 'STRUCH VET',
            show: false,
            backgroundColor: '#f3f6fb',
            webPreferences: {
                nodeIntegration: false,
                contextIsolation: true,
                sandbox: true,
                preload: path.join(__dirname, 'preload.js'),
            },
        });
        // El nombre de la aplicación permanece fijo aunque cambie el título del documento.
        window.on('page-title-updated', (event) => event.preventDefault());
        window.once('ready-to-show', () => window.show());
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
    if (app.isPackaged) setTimeout(() => updates.check(), 15000).unref();
    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) createWindow();
    });
});
app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit();
});
