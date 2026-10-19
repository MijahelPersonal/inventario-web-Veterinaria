const { contextBridge, ipcRenderer } = require('electron');
// El navegador solo puede consultar y solicitar operaciones del actualizador.
contextBridge.exposeInMainWorld('struchDesktop', {
    state: () => ipcRenderer.invoke('updates:state'),
    check: () => ipcRenderer.invoke('updates:check'),
    download: () => ipcRenderer.invoke('updates:download'),
    install: () => ipcRenderer.invoke('updates:install'),
    subscribe: (callback) => {
        const listener = (_event, state) => callback(state);
        ipcRenderer.on('updates:state', listener);
        return () => ipcRenderer.removeListener('updates:state', listener);
    },
});
