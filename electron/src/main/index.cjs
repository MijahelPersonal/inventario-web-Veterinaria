const {
  app,
  BrowserWindow,
  Menu,
  dialog,
  ipcMain,
  protocol,
  net,
  session,
} = require("electron");
const path = require("node:path"),
  fs = require("node:fs");
const { pathToFileURL } = require("node:url");
const { autoUpdater } = require("electron-updater");
const { DesktopUpdates } = require("./updates.cjs");
const { readConfig, saveConfig } = require("./config.cjs");
const { Backend } = require("./backend.cjs");
protocol.registerSchemesAsPrivileged([
  {
    scheme: "struch-app",
    privileges: { standard: true, secure: true, supportFetchAPI: true },
  },
]);
const shellURL = "struch-app://desktop/index.html";
let main,
  control,
  backend,
  config,
  updates,
  origin = "",
  closing = false,
  ready = false,
  mutating = false,
  installing = false;
const resources = app.isPackaged
  ? process.resourcesPath
  : path.resolve(__dirname, "../../resources");
const icons = app.isPackaged
  ? path.join(resources, "icons")
  : path.resolve(__dirname, "../../assets/icons");
const data = path.join(app.getPath("userData"), "data"),
  configFile = path.join(data, "desktop.json");
const state = { screen: "loading", message: "Iniciando StruchVet Desktop…" };
function publicState() {
  return {
    ...state,
    version: app.getVersion(),
    packaged: app.isPackaged,
    update: updates?.state,
    mode: config?.mode,
    configuration:
      config?.mode === "local"
        ? { mode: "local", database: { ...config.database, password: "" } }
        : config,
  };
}
function broadcast() {
  for (const w of [main, control])
    if (
      w &&
      !w.isDestroyed() &&
      w.webContents.getURL().startsWith("struch-app://desktop/")
    )
      w.webContents.send("desktop:state", publicState());
}
function set(change) {
  Object.assign(state, change);
  broadcast();
}
function options() {
  return {
    width: 1320,
    height: 860,
    minWidth: 880,
    minHeight: 620,
    title: "StruchVet Desktop",
    backgroundColor: "#f3f6fb",
    icon: path.join(icons, "512x512.png"),
    webPreferences: {
      sandbox: true,
      nodeIntegration: false,
      contextIsolation: true,
      webSecurity: true,
      preload: path.join(__dirname, "../preload/index.cjs"),
    },
  };
}
function protect(w, isControl = false) {
  w.webContents.setWindowOpenHandler(() => ({ action: "deny" }));
  w.on("page-title-updated", (e) => e.preventDefault());
  const allow = (url) => {
    try {
      const u = new URL(url);
      return (
        u.href.startsWith("struch-app://desktop/") ||
        (!isControl && origin && u.origin === origin)
      );
    } catch {
      return false;
    }
  };
  for (const event of ["will-navigate", "will-redirect"])
    w.webContents.on(event, (e, url) => {
      if (!allow(url)) e.preventDefault();
    });
  w.webContents.on("will-attach-webview", (e) => e.preventDefault());
}
function showControl(screen = "settings") {
  set({ screen });
  if (control && !control.isDestroyed()) {
    control.focus();
    broadcast();
    return;
  }
  control = new BrowserWindow({
    ...options(),
    width: 880,
    height: 740,
    parent: main,
  });
  protect(control, true);
  control.loadURL(shellURL);
  control.on("closed", () => {
    control = null;
  });
}
async function loadGestion() {
  set({ screen: "loading", message: "Conectando con Gestión…" });
  if (config.mode === "remote") {
    origin = new URL(config.url).origin;
    try {
      await main.loadURL(config.url);
    } catch {
      ready = false;
      origin = "";
      await main.loadURL(shellURL);
      throw Error(
        "No se pudo conectar con el servidor de Gestión. Revisa la dirección HTTPS, la conexión y su certificado.",
      );
    }
    ready = true;
    return;
  }
  const status = await backend.cli("status");
  if (status.compatible === false)
    throw Error(
      "Esta base fue actualizada por una versión más reciente. Instala esa versión antes de abrirla.",
    );
  if (status.recovery_required)
    throw Error(
      "Una migración anterior necesita revisión. Conserva el respaldo y solicita recuperación antes de continuar.",
    );
  if (status.pending?.length || !status.admin_exists) {
    set({
      screen: "setup",
      message:
        "La conexión funciona. Revisa la preparación de la base de datos.",
      pending: status.pending || [],
      adminRequired: !status.admin_exists,
    });
    return;
  }
  origin = await backend.start();
  await main.loadURL(origin);
  ready = true;
  set({ screen: "settings", message: "Gestión está lista." });
}
function createBackend() {
  if (app.isPackaged) require("./integrity.cjs").verifyResources(resources);
  const service = new Backend(resources, data, configFile);
  service.on("unavailable", () => {
    if (
      service === backend &&
      ready &&
      !closing &&
      main &&
      !main.isDestroyed()
    ) {
      ready = false;
      origin = "";
      set({
        screen: "error",
        message: "El servicio local se detuvo. Puedes volver a abrir Gestión.",
      });
      main.loadURL(shellURL).catch(() => {});
    }
  });
  return service;
}
async function mutate(operation) {
  if (mutating || closing || installing)
    throw Error(
      "Hay otra operación en curso. Espera a que termine antes de continuar.",
    );
  mutating = true;
  try {
    return await operation();
  } finally {
    mutating = false;
  }
}
async function backup({ restart = true } = {}) {
  if (config?.mode !== "local") return { ok: true, remote: true };
  const wasRunning = ready;
  let complete = false;
  ready = false;
  set({
    screen: "loading",
    message: "Cerrando Gestión para crear un respaldo consistente…",
  });
  if (main && !main.isDestroyed()) await main.loadURL(shellURL);
  await backend.stop();
  origin = "";
  try {
    const result = await backend.cli("backup");
    const dest = path.join(
      data,
      "backups",
      "desktop-" + new Date().toISOString().replace(/[:.]/g, "-"),
    );
    fs.mkdirSync(dest, { recursive: true, mode: 0o700 });
    fs.copyFileSync(configFile, path.join(dest, "desktop.json"));
    fs.chmodSync(path.join(dest, "desktop.json"), 0o600);
    const uploads = path.join(data, "storage", "uploads");
    if (fs.existsSync(uploads))
      fs.cpSync(uploads, path.join(dest, "uploads"), { recursive: true });
    fs.writeFileSync(
      path.join(dest, "manifest.json"),
      JSON.stringify({
        version: app.getVersion(),
        databaseBackup: result.path,
        created: new Date().toISOString(),
      }),
      { mode: 0o600 },
    );
    complete = true;
    return { ...result, files: dest };
  } finally {
    if (wasRunning && (restart || !complete) && !closing) {
      try {
        await loadGestion();
      } catch (error) {
        set({ screen: "error", message: error.message });
      }
    } else if (restart)
      set({
        screen: "settings",
        message: "Respaldo creado. Puedes abrir Gestión.",
      });
  }
}
async function manualBackup() {
  if (config?.mode !== "local") return backup();
  const answer = await dialog.showMessageBox(control || main, {
    type: "question",
    message: "¿Cerrar temporalmente Gestión y crear un respaldo?",
    detail:
      "Guarda los formularios abiertos antes de continuar. Se detendrá el servicio local mientras se respaldan la base de datos, configuración e imágenes. Gestión volverá a abrirse después.",
    buttons: ["Cancelar", "Crear respaldo"],
    defaultId: 0,
    cancelId: 0,
  });
  if (answer.response !== 1)
    throw Error("Respaldo cancelado. No se han modificado datos.");
  return backup();
}
function trusted(event) {
  if (
    ![main, control].some(
      (w) => w && !w.isDestroyed() && w.webContents === event.sender,
    ) ||
    event.senderFrame !== event.sender.mainFrame ||
    !event.senderFrame.url.startsWith("struch-app://desktop/")
  )
    throw Error("Acción no autorizada.");
}
const mutators = new Set([
  "desktop:configure",
  "desktop:prepare",
  "desktop:open",
  "desktop:backup",
  "updates:install",
]);
function handle(channel, fn) {
  ipcMain.handle(channel, async (e, ...args) => {
    trusted(e);
    try {
      return {
        ok: true,
        value: await (mutators.has(channel)
          ? mutate(() => fn(...args))
          : fn(...args)),
      };
    } catch (error) {
      return { ok: false, error: error.message.slice(0, 500) };
    }
  });
}
if (!app.requestSingleInstanceLock()) app.quit();
else {
  app.on("second-instance", () => {
    if (main) {
      if (main.isMinimized()) main.restore();
      main.focus();
    }
  });
  app
    .whenReady()
    .then(async () => {
      fs.mkdirSync(data, { recursive: true, mode: 0o700 });
      protocol.handle("struch-app", (request) => {
        const u = new URL(request.url),
          allowed = ["/index.html", "/app.js", "/style.css", "/icon.png"];
        if (u.hostname !== "desktop" || !allowed.includes(u.pathname))
          return new Response("No encontrado", { status: 404 });
        const file =
          u.pathname === "/icon.png"
            ? path.join(icons, "256x256.png")
            : path.join(__dirname, "../renderer", u.pathname.slice(1));
        return net.fetch(pathToFileURL(file).href);
      });
      session.defaultSession.setPermissionRequestHandler((_w, _p, cb) =>
        cb(false),
      );
      session.defaultSession.setPermissionCheckHandler(() => false);
      session.defaultSession.webRequest.onBeforeSendHeaders(
        (details, callback) => {
          const headers = { ...details.requestHeaders };
          delete headers["X-Struch-Desktop-Token"];
          if (backend && origin && details.url.startsWith(origin + "/"))
            headers["X-Struch-Desktop-Token"] = backend.token;
          callback({ requestHeaders: headers });
        },
      );
      main = new BrowserWindow(options());
      protect(main);
      updates = new DesktopUpdates(
        autoUpdater,
        app.getVersion(),
        app.isPackaged,
      );
      autoUpdater.allowPrerelease = false;
      autoUpdater.allowDowngrade = false;
      autoUpdater.logger = { info() {}, warn() {}, error() {}, debug() {} };
      updates.on("state", (s) => {
        broadcast();
        if (s.status === "available") showControl("updates");
      });
      handle("desktop:state", () => publicState());
      handle("desktop:configure", async (value) => {
        if (backend) await backend.stop();
        ready = false;
        origin = "";
        config = saveConfig(configFile, value);
        backend = createBackend();
        await loadGestion();
        if (ready && control) control.close();
        return publicState();
      });
      handle("desktop:prepare", async (admin) => {
        if (config?.mode !== "local")
          throw Error("Configura la base de datos primero.");
        const answer = await dialog.showMessageBox(main, {
          type: "question",
          message: "¿Preparar esta base de datos para StruchVet?",
          detail:
            "Se aplicarán únicamente migraciones pendientes. Si ya contiene tablas se creará un respaldo antes de modificarlas. No cierres la aplicación.",
          buttons: ["Cancelar", "Preparar"],
          defaultId: 0,
          cancelId: 0,
        });
        if (answer.response !== 1) return publicState();
        set({
          screen: "loading",
          message: "Preparando la base de datos y el respaldo…",
        });
        try {
          await backend.cli("migrate", null, ["--confirm"]);
          const status = await backend.cli("status");
          if (!status.admin_exists) await backend.cli("create-admin", admin);
          await loadGestion();
          if (ready && control) control.close();
          return publicState();
        } catch (e) {
          set({ screen: "setup", message: e.message });
          throw e;
        }
      });
      handle("desktop:open", async () => {
        await loadGestion();
        return publicState();
      });
      handle("desktop:backup", manualBackup);
      handle("updates:check", () => updates.check());
      handle("updates:download", () => updates.download());
      handle("updates:install", async () => {
        if (updates.state.status !== "downloaded")
          throw Error("Primero descarga una actualización válida.");
        const answer = await dialog.showMessageBox(control || main, {
          type: "question",
          message: "¿Guardar respaldo, cerrar e instalar la actualización?",
          detail:
            config?.mode === "remote"
              ? "La actualización reemplaza solamente el cliente de escritorio. Los datos del servidor HTTPS se administran y respaldan en el servidor."
              : "Guarda los formularios abiertos. Se respaldarán la base de datos, configuración e imágenes antes de instalar.",
          buttons: ["Posponer", "Instalar"],
          defaultId: 0,
          cancelId: 0,
        });
        if (answer.response === 1) {
          await backup({ restart: false });
          if (backend) await backend.stop();
          if (process.platform === "linux" && process.env.APPIMAGE) {
            const previous = process.env.APPIMAGE;
            if (!path.isAbsolute(previous) || !fs.statSync(previous).isFile()) {
              throw Error("No se pudo verificar el AppImage instalado.");
            }
            const folder = path.join(data, "backups");
            fs.mkdirSync(folder, { recursive: true, mode: 0o700 });
            fs.copyFileSync(
              previous,
              path.join(
                folder,
                `StruchVet-${app.getVersion()}-${Date.now()}.AppImage`,
              ),
            );
          }
          installing = true;
          try {
            updates.install();
          } catch (error) {
            installing = false;
            if (config?.mode === "local") await loadGestion();
            throw error;
          }
        }
        return updates.state;
      });
      Menu.setApplicationMenu(
        Menu.buildFromTemplate([
          {
            label: "StruchVet",
            submenu: [
              {
                label: "Instalar AppImage en este usuario",
                visible: process.platform === "linux" && !!process.env.APPIMAGE,
                click: async () => {
                  try {
                    const response = await dialog.showMessageBox(main, {
                      message: "¿Crear acceso en el menú de aplicaciones?",
                      detail:
                        "Se copiará StruchVet a tu carpeta Applications. No requiere permisos de administrador.",
                      buttons: ["Cancelar", "Instalar"],
                      defaultId: 0,
                      cancelId: 0,
                    });
                    if (response.response !== 1) return;
                    const home = app.getPath("home");
                    const destination =
                      require("./appimage-install.cjs").installAppImage({
                        source: process.env.APPIMAGE,
                        home,
                        dataHome:
                          process.env.XDG_DATA_HOME ||
                          path.join(home, ".local/share"),
                        icon: path.join(icons, "256x256.png"),
                      });
                    await dialog.showMessageBox(main, {
                      message: "Acceso a StruchVet creado.",
                      detail:
                        "Cierra esta ventana y abre StruchVet desde el menú de aplicaciones.\n" +
                        destination,
                    });
                  } catch (error) {
                    dialog.showErrorBox(
                      "No se pudo instalar el acceso",
                      error.message,
                    );
                  }
                },
              },
              {
                label: "Configuración y conexión",
                click: () => showControl("settings"),
              },
              {
                label: "Comprobar actualizaciones",
                click: () => {
                  showControl("updates");
                  updates.check();
                },
              },
              {
                label: "Crear respaldo",
                click: async () => {
                  try {
                    const r = await mutate(manualBackup);
                    dialog.showMessageBox(main, {
                      message: r.remote
                        ? "El respaldo se administra en el servidor."
                        : "Respaldo creado.",
                      detail: r.path || "",
                    });
                  } catch (e) {
                    dialog.showErrorBox("No se pudo respaldar", e.message);
                  }
                },
              },
              { type: "separator" },
              {
                label: "Acerca de StruchVet Desktop",
                click: () =>
                  dialog.showMessageBox(main, {
                    title: "StruchVet Desktop",
                    message: "StruchVet Desktop " + app.getVersion(),
                    detail:
                      "Gestión veterinaria · PHP + MariaDB + Electron\nMijahelPersonal",
                  }),
              },
              { role: "quit" },
            ],
          },
          {
            label: "Edición",
            submenu: [
              { role: "undo" },
              { role: "redo" },
              { type: "separator" },
              { role: "cut" },
              { role: "copy" },
              { role: "paste" },
              { role: "selectAll" },
            ],
          },
          {
            label: "Vista",
            submenu: [
              { role: "reload" },
              { role: "resetZoom" },
              { role: "zoomIn" },
              { role: "zoomOut" },
              { role: "togglefullscreen" },
            ],
          },
        ]),
      );
      await main.loadURL(shellURL);
      try {
        config = readConfig(configFile);
        backend = createBackend();
        if (config) await loadGestion();
        else
          set({
            screen: "settings",
            message:
              "Conecta StruchVet con tu base de datos o con un servidor de gestión.",
          });
      } catch (e) {
        set({ screen: "settings", message: e.message });
      }
      if (app.isPackaged) setTimeout(() => updates.check(), 15000).unref();
      main.on("closed", () => {
        main = null;
      });
    })
    .catch((e) => {
      dialog.showErrorBox("No se pudo iniciar StruchVet", e.message);
      app.quit();
    });
  app.on("window-all-closed", () => app.quit());
  app.on("before-quit", (event) => {
    if (mutating && !installing) {
      event.preventDefault();
      set({
        message: "Espera a que termine la operación antes de cerrar StruchVet.",
      });
      return;
    }
    if (!closing) {
      event.preventDefault();
      closing = true;
      Promise.resolve(backend?.stop()).finally(() => app.quit());
    }
  });
}
