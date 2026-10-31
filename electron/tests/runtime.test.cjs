"use strict";
const test = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const os = require("node:os");
const path = require("node:path");
const { Backend } = require("../src/main/backend.cjs");
const http = require("node:http");
const rawStatus = (url, headers) =>
  new Promise((resolve, reject) => {
    const req = http.get(url, { headers }, (res) => {
      res.resume();
      resolve(res.statusCode);
    });
    req.once("error", reject);
  });
const resources = path.resolve(__dirname, "../resources");
const available = fs.existsSync(path.join(resources, "runtime/runtime.json"));

test(
  "bundled PHP: private health, rendered login, authenticated assets, shutdown and restart",
  { skip: !available, timeout: 30000 },
  async (t) => {
    const data = fs.mkdtempSync(
      path.join(os.tmpdir(), "struch-desktop-runtime-"),
    );
    const config = path.join(data, "config.json");
    // Login renders without opening a database connection, but desktop configuration must be valid.
    fs.writeFileSync(
      config,
      JSON.stringify({
        database: {
          host: "127.0.0.1",
          port: 3306,
          name: "struchvet_desktop_test",
          username: "",
          password: "",
        },
      }),
      { mode: 0o600 },
    );
    const backend = new Backend(resources, data, config);
    t.after(async () => {
      await backend.stop();
      fs.rmSync(data, { recursive: true, force: true });
    });
    let url = await backend.start();
    const firstPID = backend.child.pid;
    assert.ok(firstPID > 0);
    assert.equal(new URL(url).hostname, "127.0.0.1");
    const health = await fetch(url + "/__desktop/health", {
      headers: { "X-Struch-Desktop-Token": backend.token },
    });
    assert.equal(health.status, 200);
    assert.deepEqual(await health.json(), { ok: true });
    assert.equal((await fetch(url + "/__desktop/health")).status, 403);
    assert.equal(
      (
        await fetch(url + "/__desktop/health", {
          headers: { "X-Struch-Desktop-Token": "a".repeat(64) },
        })
      ).status,
      403,
    );
    assert.equal(
      await rawStatus(url + "/__desktop/health", {
        "X-Struch-Desktop-Token": backend.token,
        Host: "attacker.example",
      }),
      403,
    );
    const login = await fetch(url + "/login", {
      headers: { "X-Struch-Desktop-Token": backend.token },
    });
    assert.equal(
      login.status,
      200,
      "Bundled application must render the real login view, not only its health route.",
    );
    const html = await login.text();
    assert.match(html, /<form\b/);
    assert.match(html, /name=["']username["']/);
    assert.match(html, /name=["']password["']/);
    const asset = "/assets/icons/heart-pulse-fill.svg";
    assert.equal((await fetch(url + asset)).status, 403);
    const image = await fetch(url + asset, {
      headers: { "X-Struch-Desktop-Token": backend.token },
    });
    assert.equal(image.status, 200);
    assert.match(await image.text(), /<svg/);
    await backend.stop();
    assert.equal(backend.child, null);
    await assert.rejects(
      fetch(url + "/__desktop/health", { signal: AbortSignal.timeout(1000) }),
    );
    url = await backend.start();
    assert.notEqual(backend.child.pid, firstPID);
    assert.equal(
      (
        await fetch(url + "/__desktop/health", {
          headers: { "X-Struch-Desktop-Token": backend.token },
        })
      ).status,
      200,
    );
  },
);
