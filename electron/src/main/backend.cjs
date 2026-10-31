const fs = require("node:fs");
const path = require("node:path");
const net = require("node:net");
const crypto = require("node:crypto");
const { spawn } = require("node:child_process");
const { EventEmitter } = require("node:events");
async function freePort() {
  return new Promise((resolve, reject) => {
    const s = net.createServer();
    s.once("error", reject);
    s.listen(0, "127.0.0.1", () => {
      const p = s.address().port;
      s.close(() => resolve(p));
    });
  });
}
class Backend extends EventEmitter {
  constructor(resources, data, config) {
    super();
    this.root = path.join(resources, "gestion");
    this.runtime = path.join(resources, "runtime");
    this.data = data;
    this.config = config;
    this.child = null;
    this.stopping = false;
    this.token = crypto.randomBytes(32).toString("hex");
  }
  command(args) {
    const r = JSON.parse(
      fs.readFileSync(path.join(this.runtime, "runtime.json"), "utf8"),
    );
    const exe = path.resolve(this.runtime, r.executable);
    if (!exe.startsWith(this.runtime + path.sep))
      throw Error("Runtime inválido.");
    return {
      exe,
      args: [
        ...(r.args || []),
        "-c",
        path.join(this.runtime, "php.ini"),
        "-d",
        "extension_dir=" + path.join(this.runtime, "ext"),
        ...args,
      ],
    };
  }
  env() {
    return {
      ...process.env,
      PHPRC: path.join(this.runtime, "php.ini"),
      PHP_INI_SCAN_DIR: "",
      STRUCH_DESKTOP_DATA: this.data,
      STRUCH_DESKTOP_CONFIG: this.config,
      STRUCH_DESKTOP_TOKEN: this.token,
    };
  }
  cli(action, input, extra = []) {
    return new Promise((resolve, reject) => {
      const c = this.command([
        path.join(this.root, "scripts", "desktop.php"),
        action,
        ...extra,
      ]);
      const child = spawn(c.exe, c.args, {
        cwd: this.runtime,
        env: this.env(),
        windowsHide: true,
        stdio: ["pipe", "pipe", "pipe"],
      });
      let out = "";
      const timeout = setTimeout(() => {
        child.kill();
        reject(
          Error(
            "La operación tardó demasiado. Revisa la conexión a la base de datos.",
          ),
        );
      }, 120000);
      child.stdout.on("data", (b) => {
        out += b;
        if (out.length > 1024 * 1024) child.kill();
      });
      child.stderr.on("data", () => {});
      child.once("error", () => {
        clearTimeout(timeout);
        reject(Error("No se pudo ejecutar el PHP incluido."));
      });
      child.once("exit", (code) => {
        clearTimeout(timeout);
        try {
          const r = JSON.parse(out);
          if (code !== 0 || !r.ok)
            reject(
              Error(
                r.message || r.error || "La operación no pudo completarse.",
              ),
            );
          else resolve(r);
        } catch (e) {
          reject(
            e instanceof SyntaxError
              ? Error(
                  "PHP no pudo completar la operación. Comprueba el runtime y la conexión.",
                )
              : e,
          );
        }
      });
      child.stdin.end(input ? JSON.stringify(input) : "");
    });
  }
  async start() {
    if (this.child) return this.url;
    this.stopping = false;
    for (let attempt = 0; attempt < 3; attempt++) {
      const port = await freePort();
      this.url = `http://127.0.0.1:${port}`;
      const c = this.command([
        "-d",
        "session.save_path=" + path.join(this.data, "storage", "sessions"),
        "-d",
        "display_errors=0",
        "-d",
        "log_errors=1",
        "-d",
        "error_log=" + path.join(this.data, "storage", "logs", "php.log"),
        "-S",
        `127.0.0.1:${port}`,
        "-t",
        path.join(this.root, "public"),
        path.join(this.root, "scripts", "desktop-router.php"),
      ]);
      for (const d of ["sessions", "logs"])
        fs.mkdirSync(path.join(this.data, "storage", d), {
          recursive: true,
          mode: 0o700,
        });
      let failed = false;
      const child = spawn(c.exe, c.args, {
        cwd: this.runtime,
        env: this.env(),
        windowsHide: true,
        stdio: "ignore",
      });
      this.child = child;
      child.on("error", () => {
        failed = true;
      });
      child.on("exit", () => {
        failed = true;
        if (this.child === child) {
          this.child = null;
          if (!this.stopping) this.emit("unavailable");
        }
      });
      for (let i = 0; i < 100 && !failed; i++) {
        try {
          const r = await fetch(this.url + "/__desktop/health", {
            headers: { "X-Struch-Desktop-Token": this.token },
            signal: AbortSignal.timeout(300),
          });
          if (r.ok && (await r.json()).ok) return this.url;
        } catch {}
        await new Promise((r) => setTimeout(r, 100));
      }
      await this.stop();
      this.stopping = false;
    }
    throw Error(
      "No se pudo iniciar el servicio local. Reinicia la aplicación o revisa los archivos de instalación.",
    );
  }
  async stop() {
    this.stopping = true;
    const child = this.child;
    this.child = null;
    if (!child || child.exitCode !== null) return;
    await new Promise((resolve) => {
      let done = false;
      const finish = () => {
        if (!done) {
          done = true;
          resolve();
        }
      };
      child.once("exit", finish);
      child.kill();
      const t = setTimeout(() => {
        child.kill("SIGKILL");
        finish();
      }, 4000);
      t.unref();
    });
  }
}
module.exports = { Backend, freePort };
