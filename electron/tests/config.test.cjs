const test = require("node:test"),
  assert = require("node:assert/strict"),
  fs = require("node:fs"),
  os = require("node:os"),
  path = require("node:path");
const {
  validateConfig,
  saveConfig,
  readConfig,
} = require("../src/main/config.cjs");
test("solo HTTPS remoto sin credenciales; SQL valida tipos y puertos", () => {
  for (const url of [
    "http://gestion.test",
    "https://user:pass@gestion.test",
    "file:///tmp/a",
    "javascript:alert(1)",
  ])
    assert.throws(() => validateConfig({ mode: "remote", url }));
  assert.equal(
    validateConfig({ mode: "remote", url: "https://gestion.test/" }).mode,
    "remote",
  );
  for (const port of [0, 65536, "x"])
    assert.throws(() =>
      validateConfig({
        mode: "local",
        database: {
          host: "localhost",
          port,
          name: "vet",
          username: "u",
          password: "",
        },
      }),
    );
  assert.throws(() =>
    validateConfig({
      mode: "local",
      database: {
        host: "localhost;bad",
        port: 3306,
        name: "vet",
        username: "u",
        password: "",
      },
    }),
  );
});
test("guarda config fuera de app con permiso privado y sin admin", () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), "struch-config-"));
  try {
    const f = path.join(dir, "profile", "desktop.json");
    saveConfig(f, {
      mode: "local",
      database: {
        host: "localhost",
        port: 3306,
        name: "struch_vet_test",
        username: "qa",
        password: "solo-prueba",
      },
      admin: { password: "no-guardar" },
    });
    const c = readConfig(f);
    assert.equal(c.database.password, "solo-prueba");
    assert.equal(c.admin, undefined);
    if (process.platform !== "win32")
      assert.equal(fs.statSync(f).mode & 0o777, 0o600);
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
});
