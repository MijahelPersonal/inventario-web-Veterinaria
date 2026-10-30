const test = require("node:test"),
  assert = require("node:assert/strict"),
  fs = require("node:fs"),
  path = require("node:path"),
  os = require("node:os"),
  crypto = require("node:crypto");
const { verifyResources } = require("../src/main/integrity.cjs");
test("rechaza runtime alterado y rutas que escapan de recursos", () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), "struch-integrity-"));
  try {
    const m = {};
    for (const s of ["runtime", "gestion"]) {
      fs.mkdirSync(path.join(dir, s));
      fs.writeFileSync(path.join(dir, s, "code"), "original");
      m[s] = {
        code: crypto.createHash("sha256").update("original").digest("hex"),
      };
    }
    const file = path.join(dir, "runtime-manifest.json");
    fs.writeFileSync(file, JSON.stringify(m));
    assert.equal(verifyResources(dir), true);
    fs.writeFileSync(path.join(dir, "runtime/code"), "corrupto");
    assert.throws(() => verifyResources(dir), /dañados/);
    m.runtime = { "../outside": m.gestion.code };
    fs.writeFileSync(file, JSON.stringify(m));
    assert.throws(() => verifyResources(dir), /inválido/);
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
});
