const fs = require("node:fs"),
  path = require("node:path");
function installAppImage({ source, home, dataHome, icon }) {
  if (
    !path.isAbsolute(source) ||
    !fs.statSync(source).isFile() ||
    ![home, dataHome].every((p) => path.isAbsolute(p) && !/[\r\n\0]/.test(p))
  )
    throw Error("No se pudo verificar la ubicación de instalación.");
  const dir = path.join(home, "Applications", "StruchVet");
  fs.mkdirSync(dir, { recursive: true, mode: 0o700 });
  const destination = path.join(dir, "StruchVet-Desktop.AppImage");
  if (path.resolve(source) !== destination) {
    fs.copyFileSync(source, destination + ".partial");
    fs.chmodSync(destination + ".partial", 0o755);
    fs.renameSync(destination + ".partial", destination);
  }
  const icons = path.join(dataHome, "icons", "hicolor", "256x256", "apps");
  fs.mkdirSync(icons, { recursive: true });
  fs.copyFileSync(icon, path.join(icons, "struchvet-desktop.png"));
  const apps = path.join(dataHome, "applications");
  fs.mkdirSync(apps, { recursive: true });
  const quoted = destination
    .replace(/\\/g, "\\\\")
    .replace(/"/g, '\\"')
    .replace(/%/g, "%%");
  fs.writeFileSync(
    path.join(apps, "struchvet-desktop.desktop"),
    `[Desktop Entry]\nType=Application\nName=StruchVet Desktop\nComment=Gestión veterinaria e inventario\nExec="${quoted}"\nIcon=struchvet-desktop\nTerminal=false\nCategories=Office;\nStartupWMClass=struchvet-desktop\n`,
    { mode: 0o644 },
  );
  return destination;
}
module.exports = { installAppImage };
