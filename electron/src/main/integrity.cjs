const fs = require("node:fs"),
  path = require("node:path"),
  crypto = require("node:crypto");
function verifyResources(
  root,
  manifestFile = path.join(root, "runtime-manifest.json"),
) {
  const m = JSON.parse(fs.readFileSync(manifestFile, "utf8"));
  for (const section of ["runtime", "gestion"]) {
    if (!m[section] || !Object.keys(m[section]).length)
      throw Error("Falta el manifiesto de la instalación.");
    for (const [relative, hash] of Object.entries(m[section])) {
      if (
        path.isAbsolute(relative) ||
        relative.split(/[\\/]/).includes("..") ||
        !/^[a-f0-9]{64}$/.test(hash)
      )
        throw Error("Manifiesto inválido.");
      const file = path.join(root, section, relative);
      if (
        fs.lstatSync(file).isSymbolicLink() ||
        crypto
          .createHash("sha256")
          .update(fs.readFileSync(file))
          .digest("hex") !== hash
      )
        throw Error(
          "La instalación tiene archivos dañados. Vuelve a instalar StruchVet desde una publicación verificada.",
        );
    }
  }
  return true;
}
module.exports = { verifyResources };
