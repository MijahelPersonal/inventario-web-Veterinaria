'use strict';
// Stages application code and a relocatable PHP distribution. Never copies local configuration/data.
const fs = require('node:fs');
const path = require('node:path');
const cp = require('node:child_process');
const crypto = require('node:crypto');
const root = path.resolve(__dirname, '../..');
const resources = path.join(root, 'electron/resources');
const runtime = path.join(resources, 'runtime');
const backend = path.join(resources, 'gestion');
const php = process.env.PHP_BINARY || (process.platform === 'win32' ? 'php.exe' : 'php');
const run = (exe, args, options = {}) => cp.execFileSync(exe, args, { encoding: 'utf8', windowsHide: true, ...options }).trim();
const phpInfo = JSON.parse(run(php, ['-r', 'echo json_encode(["binary"=>PHP_BINARY,"version"=>PHP_VERSION,"extension_dir"=>ini_get("extension_dir"),"extensions"=>get_loaded_extensions()]);']));
if (!/^8\.[345]\./.test(phpInfo.version)) throw Error('Prepare resources with a supported PHP 8.3, 8.4 or 8.5 runtime.');
for (const p of [runtime, backend]) { fs.rmSync(p, { recursive: true, force: true }); fs.mkdirSync(p, { recursive: true }); }
const copy = (source, dest) => { fs.mkdirSync(path.dirname(dest), { recursive: true }); fs.copyFileSync(source, dest); };
const required = ['bcmath', 'ctype', 'dom', 'fileinfo', 'iconv', 'mbstring', 'openssl', 'PDO', 'pdo_mysql', 'session', 'tokenizer', 'xml', 'xmlreader', 'xmlwriter'];
const missing = required.filter(x => !phpInfo.extensions.includes(x));
if (missing.length) throw Error('PHP extensions missing: ' + missing.join(', '));
const builtins = JSON.parse(run(php, ['-n', '-r', 'echo json_encode(get_loaded_extensions());']));
// Dependency order matters on Fedora (PDO and mysqlnd are separate shared extensions).
const extensions = ['mysqlnd', 'PDO', ...required, 'SimpleXML', 'gd', 'zip'].filter((x, i, a) => a.indexOf(x) === i && phpInfo.extensions.includes(x) && !builtins.includes(x));
let executable;
let provenance = [];
function copyLinuxDependencies(file) {
  const output = run('ldd', [file]);
  if (output.includes('not found')) throw Error('An unresolved shared library exists for ' + file);
  const libs = [...output.matchAll(/(?:=>\s+)?(\/[^\s()]+)/g)].map(m => m[1]);
  for (const lib of libs) {
    const dest = path.join(runtime, 'lib', path.basename(lib));
    if (!fs.existsSync(dest)) { copy(fs.realpathSync(lib), dest); provenance.push({ name: path.basename(lib), origin: 'build host shared library' }); }
  }
  return libs;
}
if (process.platform === 'linux') {
  copy(fs.realpathSync(phpInfo.binary), path.join(runtime, 'php.bin'));
  const libs = copyLinuxDependencies(phpInfo.binary);
  const loader = libs.find(x => /^ld-linux/.test(path.basename(x)));
  if (!loader) throw Error('Only glibc Linux builds are currently supported. Build musl separately.');
  for (const ext of extensions) {
    const source = path.join(phpInfo.extension_dir, ext.toLowerCase() + '.so');
    if (!fs.existsSync(source)) throw Error('Missing shared extension: ' + ext);
    copy(source, path.join(runtime, 'ext', path.basename(source))); copyLinuxDependencies(source);
  }
  // glibc uses NSS modules dynamically; newer glibc integrates DNS/files in libc itself.
  for (const candidate of ['/lib64/libnss_dns.so.2', '/lib64/libnss_files.so.2', '/lib/x86_64-linux-gnu/libnss_dns.so.2', '/lib/x86_64-linux-gnu/libnss_files.so.2']) {
    if (fs.existsSync(candidate)) copy(candidate, path.join(runtime, 'lib', path.basename(candidate)));
  }
  const loaderName = path.basename(loader);
  fs.writeFileSync(path.join(runtime, 'php-launch'), '#!/bin/sh\nset -eu\nRUNTIME_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)\nexport PHP_INI_SCAN_DIR=""\nexec "$RUNTIME_DIR/lib/' + loaderName + '" --library-path "$RUNTIME_DIR/lib" "$RUNTIME_DIR/php.bin" -c "$RUNTIME_DIR/php.ini" -d "extension_dir=$RUNTIME_DIR/ext" "$@"\n');
  fs.chmodSync(path.join(runtime, 'php-launch'), 0o755);
  fs.chmodSync(path.join(runtime, 'php.bin'), 0o755);
  for (const f of fs.readdirSync(path.join(runtime, 'lib'))) if (f.startsWith('ld-linux')) fs.chmodSync(path.join(runtime, 'lib', f), 0o755);
  executable = 'php-launch';
  // Retain distributor license texts for the actual copied runtime/shared objects.
  const licenses = path.join(runtime, 'licenses'); fs.mkdirSync(licenses);
  if (fs.existsSync('/usr/share/licenses')) {
    for (const dir of ['php-cli', 'php-common', 'glibc', 'openssl-libs', 'libxml2', 'libxcrypt', 'pcre2', 'zlib-ng-compat', 'libzip', 'oniguruma', 'libedit', 'ncurses-libs', 'xz-libs', 'gcc', 'libgcc', 'libcapstone', 'uriparser']) {
      const source = '/usr/share/licenses/' + dir;
      if (fs.existsSync(source)) fs.cpSync(source, path.join(licenses, dir), { recursive: true, dereference: true });
    }
  }
  if (fs.existsSync('/usr/share/doc')) {
    const docs = fs.readdirSync('/usr/share/doc').filter(n => /^(php8\.[345]|libc6$|libssl3|libxml2$|libpcre2-8-0$|zlib1g$|libzip4|libonig5$)/.test(n));
    for (const n of docs) if (fs.existsSync('/usr/share/doc/' + n + '/copyright')) copy('/usr/share/doc/' + n + '/copyright', path.join(licenses, n + '-copyright'));
  }
} else if (process.platform === 'win32') {
  const source = path.dirname(phpInfo.binary);
  copy(phpInfo.binary, path.join(runtime, 'php.exe'));
  for (const name of fs.readdirSync(source)) if (/\.dll$/i.test(name) || /^(license|notice|readme)/i.test(name)) {
    if (fs.statSync(path.join(source, name)).isFile()) copy(path.join(source, name), path.join(runtime, name));
  }
  for (const ext of extensions) {
    const name = 'php_' + ext.toLowerCase() + '.dll';
    copy(path.join(phpInfo.extension_dir, name), path.join(runtime, 'ext', name));
  }
  // VC runtime app-local deployment: Windows 10/11 already supply Universal CRT.
  for (const name of ['vcruntime140.dll', 'vcruntime140_1.dll', 'msvcp140.dll']) {
    const sourceDll = path.join(process.env.SystemRoot || 'C:\\Windows', 'System32', name);
    if (fs.existsSync(sourceDll)) copy(sourceDll, path.join(runtime, name));
  }
  executable = 'php.exe';
} else {
  throw Error('macOS runtime staging requires a reviewed signed PHP distribution. macOS packaging is not enabled yet.');
}
const extensionLines = extensions.map(ext => 'extension=' + (process.platform === 'win32' ? 'php_' + ext.toLowerCase() + '.dll' : ext.toLowerCase() + '.so'));
fs.writeFileSync(path.join(runtime, 'php.ini'), [
  '; StruchVet bundled PHP: application configuration and sessions live in userData.',
  'extension_dir="ext"', ...extensionLines, 'date.timezone=America/Lima', 'expose_php=Off', 'display_errors=Off', 'log_errors=On',
  'upload_max_filesize=8M', 'post_max_size=10M', 'memory_limit=256M', 'max_execution_time=60',
  'session.use_strict_mode=1', 'session.use_only_cookies=1', 'session.cookie_httponly=1', 'session.cookie_samesite=Strict',
  'allow_url_include=Off', ''
].join('\n'));
fs.writeFileSync(path.join(runtime, 'runtime.json'), JSON.stringify({ executable, args: [], version: phpInfo.version, platform: process.platform, arch: process.arch, extensions: [...new Set([...builtins, ...extensions])], provenance }, null, 2));
fs.writeFileSync(path.join(runtime, 'NOTICE.txt'), 'This package contains PHP (' + phpInfo.version + ') and the shared runtime libraries required by its build. PHP: https://www.php.net/license/3_01.txt . Shared library distributor notices are retained under licenses/ where available. Linux runtimes are built on the CI distribution, not downloaded at application startup. Windows VC runtime is redistributed app-local and requires Microsoft redistribution license review before stable release. Runtime hashes are listed in manifest.json; release integrity/provenance requires trusted signed releases, not this manifest alone.\n');
const exclude = new Set(['local.php', 'local.test.php', 'web.local.php']);
for (const dir of ['core', 'config', 'templates', 'public', 'database', 'vendor']) {
  const source = path.join(root, 'gestion', dir);
  if (!fs.existsSync(source)) throw Error('Missing backend directory: ' + dir + '; install Composer and prepare assets before staging.');
  fs.cpSync(source, path.join(backend, dir), { recursive: true, dereference: true, filter: f => {
    const base = path.basename(f); return !exclude.has(base) && !base.endsWith('.local.php') && !base.startsWith('.env') && !['storage', 'node_modules', '.git', '.gitkeep'].includes(base);
  } });
}
// View reads the management version from this non-secret package metadata.
copy(path.join(root, 'gestion/package.json'), path.join(backend, 'package.json'));
fs.mkdirSync(path.join(backend, 'scripts'));
for (const name of ['desktop.php', 'desktop-router.php', 'migrate.php']) {
  const source = path.join(root, 'gestion/scripts', name);
  if (!fs.existsSync(source)) throw Error('Desktop backend entry point not implemented: ' + name);
  copy(source, path.join(backend, 'scripts', name));
}
// Copy license symlink targets as regular files so packaged resources never escape their tree.
function flattenLinks(directory) {
  for (const ent of fs.readdirSync(directory, { withFileTypes: true })) {
    const file = path.join(directory, ent.name);
    if (ent.isSymbolicLink()) {
      const content = fs.readFileSync(file); fs.unlinkSync(file); fs.writeFileSync(file, content);
    } else if (ent.isDirectory()) flattenLinks(file);
  }
}
flattenLinks(runtime); flattenLinks(backend);
function manifest(directory) {
  const result = {};
  function walk(base) {
    for (const entry of fs.readdirSync(base, { withFileTypes: true })) {
      const file = path.join(base, entry.name);
      if (entry.isDirectory()) walk(file);
      else result[path.relative(directory, file).split(path.sep).join('/')] = crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
    }
  }
  walk(directory); return result;
}
fs.writeFileSync(path.join(resources, 'manifest.json'), JSON.stringify({ schema: 1, php: phpInfo.version, platform: process.platform, arch: process.arch, runtime: manifest(runtime), gestion: manifest(backend) }, null, 2));
const launch = path.join(runtime, executable);
const opts = { cwd: runtime, env: { ...process.env, PHP_INI_SCAN_DIR: '', PHPRC: path.join(runtime, 'php.ini') } };
const reported = JSON.parse(run(launch, ['-r', 'echo json_encode(get_loaded_extensions());'], opts));
if (required.some(x => !reported.includes(x))) throw Error('Bundled PHP extension verification failed.');
console.log('Resources staged without user data: PHP ' + phpInfo.version + ', ' + process.platform + '/' + process.arch + ', ' + Object.keys(manifest(runtime)).length + ' runtime files.');
