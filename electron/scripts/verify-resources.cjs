'use strict';
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const root = path.resolve(__dirname, '../resources');
const manifest = JSON.parse(fs.readFileSync(path.join(root, 'manifest.json')));
let count = 0;
for (const section of ['runtime', 'gestion']) {
  const expected = manifest[section];
  if (!expected || Object.keys(expected).length === 0) throw Error('Missing resource manifest: ' + section);
  const actual = [];
  const visit = directory => {
    for (const ent of fs.readdirSync(directory, { withFileTypes: true })) {
      const file = path.join(directory, ent.name);
      if (ent.isSymbolicLink()) throw Error('Unexpected symlink in staged resources.');
      if (ent.isDirectory()) visit(file);
      else actual.push(path.relative(path.join(root, section), file).split(path.sep).join('/'));
    }
  };
  visit(path.join(root, section));
  if (actual.length !== Object.keys(expected).length) throw Error('Untracked or missing staged resource files: ' + section);
  for (const [relative, hash] of Object.entries(expected)) {
    if (relative.split('/').includes('..') || path.isAbsolute(relative)) throw Error('Invalid manifest path.');
    if (section === 'gestion' && /(^|\/)(storage|node_modules|\.env[^/]*|local\.php|local\.test\.php|web\.local\.php)(\/|$)/.test(relative)) throw Error('Private data found in build: ' + relative);
    const file = path.join(root, section, relative);
    if (!/^[a-f0-9]{64}$/.test(hash) || crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex') !== hash) throw Error('Resource integrity mismatch: ' + relative);
    count++;
  }
}
for (const name of ['package.json','core/framework/Database.php','public/index.php','vendor/autoload.php','scripts/desktop.php','scripts/desktop-router.php']) if (!fs.existsSync(path.join(root, 'gestion', name))) throw Error('Missing backend resource: ' + name);
console.log('Verified ' + count + ' resource SHA256 hashes; no local credentials, uploads or databases staged.');
