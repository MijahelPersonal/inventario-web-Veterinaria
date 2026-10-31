#!/usr/bin/env python3
"""Render the existing StruchVet vector mark, without redrawing its paths."""
from pathlib import Path
import io, subprocess, shutil, hashlib, json
from PIL import Image
root = Path(__file__).resolve().parents[2]
source = root / 'gestion/public/assets/icons/heart-pulse-fill.svg'
out = root / 'electron/assets/icons'
out.mkdir(parents=True, exist_ok=True)
(out / 'png').mkdir(exist_ok=True)
svg = source.read_text().replace('fill="currentColor"', 'fill="#2563eb"')
(out / 'struchvet.svg').write_text(svg)
# Render at high resolution with transparent padding for taskbars and application menus.
try:
    import cairosvg
    rendered = cairosvg.svg2png(bytestring=svg.encode(), output_width=880, output_height=880)
except ImportError:
    converter = shutil.which('magick') or shutil.which('convert')
    if not converter:
        raise SystemExit('Install Pillow and CairoSVG to regenerate icons (build uses the committed icons).')
    rendered = subprocess.check_output([converter, '-background', 'none', '-density', '5280', 'svg:-', '-resize', '880x880', 'png:-'], input=svg.encode())
im = Image.open(io.BytesIO(rendered)).convert('RGBA')
canvas = Image.new('RGBA', (1024, 1024), (255, 255, 255, 0))
canvas.alpha_composite(im, ((1024-im.width)//2, (1024-im.height)//2))
for size in [16, 24, 32, 48, 64, 128, 256, 512, 1024]:
    canvas.resize((size, size), Image.Resampling.LANCZOS).save(out / f'{size}x{size}.png')
    canvas.resize((size, size), Image.Resampling.LANCZOS).save(out / 'png' / f'{size}x{size}.png')
canvas.save(out / 'icon.png')
canvas.save(out / 'icon.ico', sizes=[(16,16),(24,24),(32,32),(48,48),(64,64),(128,128),(256,256)])
canvas.save(out / 'icon.icns')
(out / 'ORIGEN.md').write_text('''# Icono StruchVet Desktop\n\nSe conserva el trazado original de `gestion/public/assets/icons/heart-pulse-fill.svg`, el mismo recurso usado por StruchVet Web. Color de marca: `#2563eb`. No se ha generado ni sustituido el símbolo por otra imagen.\n\nLas variantes PNG, ICO e ICNS se regeneran con `python3 electron/scripts/prepare-icons.py` (Pillow y CairoSVG o ImageMagick). Los PNG incluyen margen transparente para legibilidad en la barra de tareas.\n\nEl recurso original deriva del icono Bootstrap Icons `heart-pulse-fill`, bajo licencia MIT. La licencia se conserva junto al recurso distribuido en `LICENSE-bootstrap-icons.txt`.\n''')
license_file = root / 'gestion/node_modules/bootstrap-icons/LICENSE.md'
if not license_file.exists():
    license_file = root / 'gestion/node_modules/bootstrap-icons/LICENSE'
if license_file.exists():
    shutil.copyfile(license_file, out / 'LICENSE-bootstrap-icons.txt')
else:
    raise SystemExit('Install gestion npm dependencies to preserve the Bootstrap Icons license.')
print('Original StruchVet mark rendered: 9 PNG sizes, ICO, ICNS and SVG.')
