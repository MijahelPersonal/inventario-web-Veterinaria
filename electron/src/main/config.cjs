const fs = require("node:fs");
const path = require("node:path");
function validateConfig(value) {
  if (!value || typeof value !== "object" || Array.isArray(value))
    throw Error("Configuración inválida.");
  if (value.mode === "remote") {
    const u = new URL(value.url);
    if (
      u.protocol !== "https:" ||
      u.username ||
      u.password ||
      u.search ||
      u.hash
    )
      throw Error("El servidor debe usar HTTPS y no incluir credenciales.");
    return { mode: "remote", url: u.href };
  }
  if (value.mode !== "local") throw Error("Selecciona el modo de conexión.");
  const d = value.database;
  if (!d || typeof d !== "object")
    throw Error("Completa la conexión a la base de datos.");
  const text = (key, max) => {
    if (
      typeof d[key] !== "string" ||
      d[key].length > max ||
      /[\0\r\n]/.test(d[key])
    )
      throw Error("Campo de conexión inválido.");
    return d[key];
  };
  const host = text("host", 253),
    name = text("name", 64),
    username = text("username", 80),
    password = text("password", 1024);
  if (
    !host ||
    !/^[a-zA-Z0-9._:-]+$/.test(host) ||
    !/^[a-zA-Z0-9_]+$/.test(name) ||
    !username
  )
    throw Error("Servidor, base o usuario inválidos.");
  const port = Number(d.port);
  if (!Number.isInteger(port) || port < 1 || port > 65535)
    throw Error("Puerto inválido.");
  const ssl_ca =
    d.ssl_ca === undefined || d.ssl_ca === ""
      ? undefined
      : text("ssl_ca", 1024);
  if (ssl_ca && !path.isAbsolute(ssl_ca))
    throw Error("El certificado CA debe tener una ruta absoluta.");
  return {
    mode: "local",
    database: {
      host,
      port,
      name,
      username,
      password,
      ...(ssl_ca ? { ssl_ca } : {}),
    },
  };
}
function saveConfig(file, value) {
  const data = validateConfig(value);
  fs.mkdirSync(path.dirname(file), { recursive: true, mode: 0o700 });
  const temp = file + ".tmp";
  fs.writeFileSync(temp, JSON.stringify(data, null, 2), { mode: 0o600 });
  fs.chmodSync(temp, 0o600);
  fs.renameSync(temp, file);
  return data;
}
function readConfig(file) {
  return fs.existsSync(file)
    ? validateConfig(JSON.parse(fs.readFileSync(file, "utf8")))
    : null;
}
module.exports = { validateConfig, saveConfig, readConfig };
