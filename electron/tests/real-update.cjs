// Real AppImageUpdater integration: HTTP metadata, SHA512 download, replacement of a disposable AppImage.
// This test NEVER points at GitHub or the developer's installed app/profile.
const { app } = require("electron");
const fs = require("node:fs"),
  path = require("node:path"),
  http = require("node:http"),
  assert = require("node:assert/strict"),
  crypto = require("node:crypto");
const { AppImageUpdater } = require("electron-updater");
const { DesktopUpdates } = require("../src/main/updates.cjs");
const directory = process.env.STRUCH_UPDATE_FIXTURE;
if (!directory) throw Error("STRUCH_UPDATE_FIXTURE is required.");
const profile = path.join(directory, "profile");
fs.mkdirSync(profile, { recursive: true });
app.setPath("userData", profile);
app.getVersion = () => require("../package.json").version;
const image = path.join(directory, "StruchVet-test.AppImage");
process.env.APPIMAGE = image;
process.env.APPIMAGE_EXTRACT_AND_RUN = "1";
const marker = path.join(profile, "preserved.txt");
fs.writeFileSync(marker, "Datos del usuario que deben conservarse");
if (fs.existsSync(path.join(directory, "error.txt")))
  fs.unlinkSync(path.join(directory, "error.txt"));
let server;
let corruptDownload = true;
const protectedFiles = [
  "data/desktop.json",
  "data/storage/uploads/products/preserved.txt",
];
const originalHashes = Object.fromEntries(
  protectedFiles.map((f) => [
    f,
    crypto
      .createHash("sha256")
      .update(fs.readFileSync(path.join(profile, f)))
      .digest("hex"),
  ]),
);
app.whenReady().then(async () => {
  try {
    const latest = path.join(directory, "feed/latest-linux.yml");
    assert.ok(fs.existsSync(latest));
    assert.ok(fs.existsSync(image));
    server = http.createServer((req, res) => {
      const url = new URL(req.url, "http://localhost");
      const name = path.basename(decodeURIComponent(url.pathname));
      const file = path.join(directory, "feed", name);
      if (!fs.existsSync(file)) {
        res.writeHead(404);
        return res.end();
      }
      res.setHeader("Content-Length", fs.statSync(file).size);
      const stream = fs.createReadStream(file);
      if (corruptDownload && file.endsWith(".AppImage")) {
        const { Transform } = require("node:stream");
        let first = true;
        stream
          .pipe(
            new Transform({
              transform(chunk, _encoding, cb) {
                if (first) {
                  chunk[0] ^= 255;
                  first = false;
                }
                cb(null, chunk);
              },
            }),
          )
          .pipe(res);
      } else stream.pipe(res);
    });
    await new Promise((r) => server.listen(0, "127.0.0.1", r));
    const updater = new AppImageUpdater({
      provider: "generic",
      url: `http://127.0.0.1:${server.address().port}`,
    });
    updater.on("error", (e) =>
      console.error("Updater verification:", e.message),
    );
    const updateConfig = path.join(directory, "dev-app-update.yml");
    fs.writeFileSync(
      updateConfig,
      `provider: generic\nurl: http://127.0.0.1:${server.address().port}\nupdaterCacheDirName: struchvet-update-qa\n`,
    );
    updater.updateConfigPath = updateConfig;
    updater.forceDevUpdateConfig = true;
    updater.disableDifferentialDownload = true;
    updater.logger = { info() {}, warn() {}, error() {}, debug() {} };
    const updates = new DesktopUpdates(updater, "0.3.0", true);
    const states = [];
    updates.on("state", (s) => states.push({ ...s }));
    await updates.check();
    assert.equal(updates.state.status, "available");
    assert.equal(updates.state.version, "0.3.1");
    await updates.download();
    assert.equal(
      updates.state.status,
      "error",
      "A corrupted package must fail SHA512 verification",
    );
    corruptDownload = false;
    await updates.check();
    assert.equal(updates.state.status, "available");
    await updates.download();
    assert.equal(updates.state.status, "downloaded");
    for (const [file, hash] of Object.entries(originalHashes))
      assert.equal(
        crypto
          .createHash("sha256")
          .update(fs.readFileSync(path.join(profile, file)))
          .digest("hex"),
        hash,
      );
    const incoming = fs
      .readdirSync(path.join(directory, "feed"))
      .find((x) => x.endsWith(".AppImage"));
    const expected = crypto
      .createHash("sha512")
      .update(fs.readFileSync(path.join(directory, "feed", incoming)))
      .digest("hex");
    // AppImageUpdater.install is the actual library installation path; no replacement double.
    assert.equal(updater.install(false, false), true);
    assert.equal(
      crypto.createHash("sha512").update(fs.readFileSync(image)).digest("hex"),
      expected,
    );
    assert.equal(
      fs.readFileSync(marker, "utf8"),
      "Datos del usuario que deben conservarse",
    );
    for (const [file, hash] of Object.entries(originalHashes)) {
      assert.equal(
        crypto
          .createHash("sha256")
          .update(fs.readFileSync(path.join(profile, file)))
          .digest("hex"),
        hash,
      );
    }
    fs.writeFileSync(
      path.join(directory, "result.json"),
      JSON.stringify(
        {
          ok: true,
          from: "0.3.0",
          to: "0.3.1",
          verifiedSHA512: true,
          replacedActualAppImage: true,
          userProfilePreserved: true,
          corruptedDownloadRejected: true,
          configurationAndUploadsPreserved: true,
          states: states.map((s) => s.status),
        },
        null,
        2,
      ),
    );
    server.close();
    app.quit();
  } catch (e) {
    console.error(e);
    fs.writeFileSync(path.join(directory, "error.txt"), e.stack);
    server?.close();
    app.exit(1);
  }
});
setTimeout(() => app.exit(1), 90000).unref();
