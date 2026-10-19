const { EventEmitter } = require('node:events');

// Expone estados simples y exige una acción explícita para descargar o instalar.
class DesktopUpdates extends EventEmitter {
    constructor(updater, version, packaged) {
        super();
        this.updater = updater;
        this.packaged = packaged;
        this.state = { status: packaged ? 'idle' : 'development', installed: version };
        updater.autoDownload = false;
        updater.autoInstallOnAppQuit = false;
        updater.on('checking-for-update', () => this.set({ status: 'checking' }));
        updater.on('update-not-available', () => this.set({ status: 'current' }));
        updater.on('update-available', (info) =>
            this.set({
                status: 'available',
                version: info.version,
                notes: String(typeof info.releaseNotes === 'string' ? info.releaseNotes : '')
                    .replace(/<[^>]*>/g, '')
                    .slice(0, 3000),
            }),
        );
        updater.on('download-progress', (progress) =>
            this.set({
                status: 'downloading',
                progress: Math.round(progress.percent),
            }),
        );
        updater.on('update-downloaded', () => this.set({ status: 'downloaded' }));
        updater.on('error', () => this.set({ status: 'error' }));
    }
    set(change) {
        this.state = { ...this.state, ...change };
        this.emit('state', this.state);
    }
    async check() {
        if (!this.packaged || !['idle', 'current', 'error'].includes(this.state.status))
            return this.state;
        this.set({ status: 'checking' });
        try {
            await this.updater.checkForUpdates();
        } catch {
            this.set({ status: 'error' });
        }
        return this.state;
    }
    async download() {
        if (!this.packaged || this.state.status !== 'available') return this.state;
        this.set({ status: 'downloading', progress: 0 });
        try {
            await this.updater.downloadUpdate();
        } catch {
            this.set({ status: 'error' });
        }
        return this.state;
    }
    install() {
        if (this.packaged && this.state.status === 'downloaded') this.updater.quitAndInstall();
        return this.state;
    }
}
module.exports = { DesktopUpdates };
