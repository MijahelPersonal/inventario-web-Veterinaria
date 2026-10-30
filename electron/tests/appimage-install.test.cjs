const test = require("node:test"),
  assert = require("node:assert/strict"),
  fs = require("node:fs"),
  os = require("node:os"),
  path = require("node:path");
const { installAppImage } = require("../src/main/appimage-install.cjs");
test("instalación por usuario copia AppImage y crea menú e icono sin datos privados", () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), "struch-appimage-"));
  try {
    const source = path.join(dir, "download.AppImage"),
      icon = path.join(dir, "icon.png");
    fs.writeFileSync(source, "test-application");
    fs.writeFileSync(icon, "test-icon");
    const args = {
      source,
      icon,
      home: path.join(dir, "usuario con espacios"),
      dataHome: path.join(dir, "data"),
    };
    const result = installAppImage(args);
    assert.equal(fs.readFileSync(result, "utf8"), "test-application");
    const desktop = fs.readFileSync(
      path.join(args.dataHome, "applications/struchvet-desktop.desktop"),
      "utf8",
    );
    assert.match(desktop, /Exec=".*usuario con espacios.*"/);
    assert.match(desktop, /Terminal=false/);
    assert.ok(
      fs.existsSync(
        path.join(
          args.dataHome,
          "icons/hicolor/256x256/apps/struchvet-desktop.png",
        ),
      ),
    );
    assert.equal(installAppImage({ ...args, source: result }), result);
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
});
