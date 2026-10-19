const test = require('node:test');
const assert = require('node:assert/strict');
const { EventEmitter } = require('node:events');
const { DesktopUpdates } = require('../electron/updates');

test('solo descarga e instala después de las acciones y estados permitidos', async () => {
    const updater = new EventEmitter();
    let checked = 0, downloaded = 0, installed = 0;
    updater.checkForUpdates = async () => { checked++; updater.emit('update-available', {
        version: '0.3.0', releaseNotes: '<p>Mejoras en citas</p>',
    }); };
    updater.downloadUpdate = async () => { downloaded++; updater.emit('download-progress', {percent: 50}); updater.emit('update-downloaded'); };
    updater.quitAndInstall = () => installed++;
    const updates = new DesktopUpdates(updater, '0.2.0', true);
    updates.install(); await updates.download();
    assert.equal(installed, 0); assert.equal(downloaded, 0);
    await updates.check();
    assert.equal(checked, 1); assert.equal(downloaded, 0);
    assert.equal(updates.state.notes, 'Mejoras en citas');
    assert.equal(updater.autoDownload, false);
    assert.equal(updater.autoInstallOnAppQuit, false);
    await updates.download();
    assert.equal(updates.state.status, 'downloaded');
    updates.install(); assert.equal(installed, 1);
});

test('desarrollo no consulta la red y los fallos permiten reintentar', async () => {
    const updater = new EventEmitter();
    let checked = 0;
    updater.checkForUpdates = async () => { checked++; throw new Error('Sin conexión'); };
    const dev = new DesktopUpdates(updater, '0.2.0', false);
    await dev.check(); assert.equal(checked, 0);
    const installed = new DesktopUpdates(updater, '0.2.0', true);
    await installed.check(); assert.equal(installed.state.status, 'error');
    await installed.check(); assert.equal(checked, 2);
});
