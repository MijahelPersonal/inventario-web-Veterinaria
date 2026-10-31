// Executed by Electron itself, not Node; no debugging ports or disabled sandbox.
// Loads the SAME installed main module, using an isolated user profile and bundled resources.
const { app, BrowserWindow } = require("electron");
const fs = require("node:fs"),
  path = require("node:path"),
  assert = require("node:assert/strict");
const output = process.env.STRUCH_QA_OUTPUT,
  installed = process.env.STRUCH_QA_INSTALLED;
if (!output || !installed)
  throw Error("Set STRUCH_QA_OUTPUT and STRUCH_QA_INSTALLED.");
fs.mkdirSync(output, { recursive: true });
app.setPath("userData", path.join(output, "profile"));
// process.resourcesPath is configurable in Electron; no app security settings are changed.
Object.defineProperty(process, "resourcesPath", {
  value: path.join(installed, "resources"),
});
Object.defineProperty(app, "isPackaged", { value: true });
const packageFile = path.join(installed, "resources/app.asar/package.json");
const version = JSON.parse(fs.readFileSync(packageFile)).version;
app.getVersion = () => version;
const config = process.env.STRUCH_QA_CONFIG;
if (
  config &&
  !fs.existsSync(path.join(app.getPath("userData"), "data/desktop.json"))
) {
  fs.mkdirSync(path.join(app.getPath("userData"), "data"), { recursive: true });
  fs.copyFileSync(
    config,
    path.join(app.getPath("userData"), "data/desktop.json"),
  );
}
const delay = (ms) => new Promise((r) => setTimeout(r, ms));
app.whenReady().then(async () => {
  try {
    let window;
    for (let i = 0; i < 100; i++) {
      window = BrowserWindow.getAllWindows()[0];
      if (
        window &&
        !window.webContents.isLoading() &&
        window.webContents.getURL()
      )
        break;
      await delay(100);
    }
    assert.ok(window);
    await delay(1500);
    const prefs = window.webContents.getLastWebPreferences();
    assert.equal(prefs.nodeIntegration, false);
    assert.equal(prefs.contextIsolation, true);
    assert.equal(prefs.sandbox, true);
    assert.equal(
      await window.webContents.executeJavaScript("typeof require"),
      "undefined",
    );
    const isGestion = window.webContents
      .getURL()
      .startsWith("http://127.0.0.1:");
    if (process.env.STRUCH_QA_FAILURE === "1") {
      assert.match(window.webContents.getURL(), /^struch-app:\/\/desktop/);
      const status = await window.webContents.executeJavaScript(
        "window.desktop.state()",
      );
      assert.match(status.message, /No se pudo conectar con el servidor/);
      assert.equal(status.screen, "settings");
      fs.writeFileSync(
        path.join(output, "conexion-error.png"),
        (await window.webContents.capturePage()).toPNG(),
      );
    } else if (config) {
      assert.ok(
        isGestion,
        "Expected configured Gestion, got " +
          window.webContents.getURL() +
          " " +
          (await window.webContents.executeJavaScript(
            "document.body.innerText",
          )),
      );
      const hasLogin = await window.webContents.executeJavaScript(
        '!!document.querySelector("input[name=username]")',
      );
      assert.ok(
        hasLogin,
        await window.webContents.executeJavaScript("document.body.innerText"),
      );
      // Renderer cannot invoke privileged actions even though the preload exposes a finite bridge.
      const denied = await window.webContents.executeJavaScript(
        "window.desktop.state().then(()=>false,()=>true)",
      );
      assert.equal(denied, true);
      fs.writeFileSync(
        path.join(output, "login.png"),
        (await window.webContents.capturePage()).toPNG(),
      );
      const credentials = JSON.parse(
        fs.readFileSync(process.env.STRUCH_QA_LOGIN, "utf8"),
      );
      await window.webContents.executeJavaScript(
        `document.querySelector('input[name=username]').value=${JSON.stringify(credentials.username)}; document.querySelector('input[name=password]').value=${JSON.stringify(credentials.password)};document.querySelector('form').requestSubmit();`,
      );
      await delay(1200);
      assert.ok(!window.webContents.getURL().endsWith("/login"));
      const base = new URL(window.webContents.getURL()).origin;
      await window.loadURL(base + "/inventario");
      assert.match(
        await window.webContents.executeJavaScript("document.body.innerText"),
        /Inventario/i,
      );
      if (process.env.STRUCH_QA_INVENTORY === "1") {
        const cfg = JSON.parse(fs.readFileSync(config, "utf8"));
        assert.ok(cfg.database.name.endsWith("_test"));
        const lot = "desktop-ui-" + Date.now();
        await window.webContents.executeJavaScript(
          `(()=>{const f=document.querySelector('form:has(input[name=lot_code])');const p=[...f.elements.product_id.options].find(o=>o.text.includes('Producto de verificación escritorio'));f.elements.product_id.value=p.value;f.elements.kind.value='inicial';f.elements.quantity.value='2';f.elements.cost.value='9.50';f.elements.lot_code.value=${JSON.stringify(lot)};f.elements.reason.value='Entrada de verificación de escritorio';f.requestSubmit();})()`,
        );
        await delay(900);
        assert.match(
          await window.webContents.executeJavaScript("document.body.innerText"),
          /14[.,]000/,
        );
        await window.webContents.executeJavaScript(
          `(()=>{const f=document.querySelector('form:has(select[name=lot_id])');const p=[...f.elements.product_id.options].find(o=>o.text.includes('Producto de verificación escritorio'));f.elements.product_id.value=p.value;const l=[...f.elements.lot_id.options].find(o=>o.text.includes(${JSON.stringify(lot)}));f.elements.lot_id.value=l.value;f.elements.kind.value='consumo';f.elements.quantity.value='1';f.elements.reason.value='Salida de verificación de escritorio';f.requestSubmit();})()`,
        );
        await delay(900);
        assert.match(
          await window.webContents.executeJavaScript("document.body.innerText"),
          /13[.,]000/,
        );
      }
      fs.writeFileSync(
        path.join(output, "inventario.png"),
        (await window.webContents.capturePage()).toPNG(),
      );
    } else {
      assert.match(window.webContents.getURL(), /^struch-app:\/\/desktop/);
      assert.match(
        await window.webContents.executeJavaScript("document.body.innerText"),
        /MySQL\/MariaDB/,
      );
      const status = await window.webContents.executeJavaScript(
        "window.desktop.state()",
      );
      assert.equal(status.version, version);
      assert.match(status.message, /Conecta StruchVet/);
      assert.doesNotMatch(
        await window.webContents.executeJavaScript("document.body.innerText"),
        /ENOENT|archivos dañados/,
      );
      fs.writeFileSync(
        path.join(output, "inicio.png"),
        (await window.webContents.capturePage()).toPNG(),
      );
    }
    const processes = app.getAppMetrics();
    fs.writeFileSync(
      path.join(output, "result.json"),
      JSON.stringify(
        {
          ok: true,
          version,
          configured: !!config,
          inventoryEntryAndExit: process.env.STRUCH_QA_INVENTORY === "1",
          remoteFailureHandled: process.env.STRUCH_QA_FAILURE === "1",
          sandbox: true,
          nodeIntegration: false,
          contextIsolation: true,
          processes: processes.length,
        },
        null,
        2,
      ),
    );
    app.quit();
  } catch (e) {
    const w = BrowserWindow.getAllWindows()[0];
    if (w)
      fs.writeFileSync(
        path.join(output, "failure.png"),
        (await w.webContents.capturePage()).toPNG(),
      );
    fs.writeFileSync(path.join(output, "error.txt"), e.stack);
    console.error(e);
    app.exit(1);
  }
});
require(path.join(installed, "resources/app.asar/src/main/index.cjs"));
setTimeout(() => {
  console.error("GUI smoke timeout");
  app.exit(1);
}, 30000).unref();
