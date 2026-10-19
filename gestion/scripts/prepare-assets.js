const { mkdirSync, copyFileSync } = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
for (const directory of ['css', 'js', 'vendor']) {
    mkdirSync(path.join(root, 'public/assets', directory), { recursive: true });
}
for (const [source, target] of [
    ['assets/css/app.css', 'public/assets/css/app.css'],
    ['assets/js/app.js', 'public/assets/js/app.js'],
    ['node_modules/@coreui/coreui/dist/css/coreui.min.css', 'public/assets/vendor/coreui.min.css'],
    [
        'node_modules/@coreui/coreui/dist/js/coreui.bundle.min.js',
        'public/assets/vendor/coreui.bundle.min.js',
    ],
]) {
    copyFileSync(path.join(root, source), path.join(root, target));
}
console.log('Recursos públicos preparados.');
