const { contextBridge, ipcRenderer } = require("electron");
const invoke = async (channel, ...args) => {
  const r = await ipcRenderer.invoke(channel, ...args);
  if (!r.ok) throw Error(r.error);
  return r.value;
};
contextBridge.exposeInMainWorld(
  "desktop",
  Object.freeze({
    state: () => invoke("desktop:state"),
    configure: (value) => invoke("desktop:configure", value),
    prepare: (admin) => invoke("desktop:prepare", admin),
    open: () => invoke("desktop:open"),
    backup: () => invoke("desktop:backup"),
    check: () => invoke("updates:check"),
    download: () => invoke("updates:download"),
    install: () => invoke("updates:install"),
    subscribe: (callback) => {
      if (typeof callback !== "function") return () => {};
      const fn = (_event, value) => callback(value);
      ipcRenderer.on("desktop:state", fn);
      return () => ipcRenderer.removeListener("desktop:state", fn);
    },
  }),
);
