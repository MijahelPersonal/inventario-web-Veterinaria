const $ = (id) => document.getElementById(id),
  api = window.desktop;
let current;
function screen(name) {
  for (const id of ["settings", "setup", "updates"]) $(id).hidden = id !== name;
}
function render(s) {
  current = s;
  $("version").textContent = "Versión " + s.version;
  $("message").textContent = s.message || "";
  screen(s.screen);
  $("open").hidden = !s.mode;
  $("backup").hidden = s.mode !== "local";
  const u = s.update || {},
    statuses = {
      idle: "Todavía no se han comprobado actualizaciones.",
      development:
        "Las actualizaciones se comprueban en la aplicación instalada.",
      checking: "Buscando nuevas versiones…",
      current: "Tienes la versión más reciente publicada.",
      available: "Hay una nueva versión disponible.",
      downloading: "Descargando…",
      downloaded: "Descarga verificada. Lista para instalar.",
      error:
        "No se pudo completar la actualización. Revisa tu conexión e inténtalo de nuevo.",
    };
  $("update-status").textContent = statuses[u.status] || "";
  $("new-version").textContent = u.version
    ? "Instalada: " + s.version + " · Disponible: " + u.version
    : "";
  $("notes").textContent = u.notes || "";
  $("progress").hidden = u.status !== "downloading";
  $("progress").value = u.progress || 0;
  $("download").hidden = u.status !== "available";
  $("install").hidden = u.status !== "downloaded";
  $("check").disabled =
    ["checking", "downloading"].includes(u.status) || !s.packaged;
  $("pending").textContent =
    (s.pending?.length ? s.pending.length + " migraciones pendientes. " : "") +
    "Los datos existentes se conservan.";
  $("admin-fields").hidden = !s.adminRequired;
  for (const el of $("admin-fields").querySelectorAll("input"))
    el.required = !!s.adminRequired && el.name !== "email";
}
function fill(s) {
  const c = s.configuration;
  if (!c) return;
  const f = $("configuration");
  f.elements.mode.value = c.mode;
  if (c.mode === "local") {
    for (const [k, v] of Object.entries(c.database))
      if (f.elements[k]) f.elements[k].value = v;
  } else f.elements.url.value = c.url;
  toggleMode();
}
function toggleMode() {
  const remote = $("mode").value === "remote";
  $("database-fields").hidden = remote;
  $("remote-fields").hidden = !remote;
  for (const e of $("database-fields").querySelectorAll("input"))
    e.disabled = remote;
  $("configuration").elements.url.disabled = !remote;
}
async function run(fn) {
  $("error").textContent = "";
  const buttons = [...document.querySelectorAll("button")];
  buttons.forEach((b) => (b.disabled = true));
  try {
    await fn();
  } catch (e) {
    $("error").textContent = e.message;
  } finally {
    buttons.forEach((b) => (b.disabled = false));
    if (current) render(current);
  }
}
$("mode").addEventListener("change", toggleMode);
toggleMode();
$("configuration").addEventListener("submit", (e) => {
  e.preventDefault();
  run(async () => {
    const f = new FormData(e.target),
      mode = f.get("mode"),
      c =
        mode === "remote"
          ? { mode, url: f.get("url") }
          : {
              mode,
              database: Object.fromEntries(
                ["host", "port", "name", "username", "password", "ssl_ca"].map(
                  (k) => [k, f.get(k)],
                ),
              ),
            };
    render(await api.configure(c));
    e.target.elements.password.value = "";
  });
});
$("prepare").addEventListener("submit", (e) => {
  e.preventDefault();
  run(async () => {
    render(await api.prepare(Object.fromEntries(new FormData(e.target))));
    e.target.elements.password.value = "";
  });
});
$("settings-tab").onclick = () => {
  if (current) {
    current.screen = "settings";
    render(current);
  }
};
$("updates-tab").onclick = () => {
  if (current) {
    current.screen = "updates";
    render(current);
  }
};
$("later").onclick = () => {
  current.screen = "settings";
  render(current);
};
$("check").onclick = () =>
  run(async () => {
    await api.check();
  });
$("download").onclick = () =>
  run(async () => {
    await api.download();
  });
$("install").onclick = () =>
  run(async () => {
    await api.install();
  });
$("open").onclick = () => run(async () => render(await api.open()));
$("backup").onclick = () =>
  run(async () => {
    const b = await api.backup();
    current.message = "Respaldo creado: " + b.path;
  });
api.subscribe(render);
api
  .state()
  .then((s) => {
    render(s);
    fill(s);
  })
  .catch((e) => {
    $("error").textContent = e.message;
  });
