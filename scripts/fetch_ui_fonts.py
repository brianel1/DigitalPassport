"""Self-host the selected Google Fonts so the UI works without font CDN requests."""
import re
from pathlib import Path
from urllib.request import Request, urlopen

root = Path(__file__).resolve().parents[1]
out = root / 'frontend/public/fonts'
out.mkdir(parents=True, exist_ok=True)
url = 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Work+Sans:wght@400;500;600;700&display=swap'
request = Request(url, headers={'User-Agent': 'Mozilla/5.0 Chrome/131.0.0.0 Safari/537.36'})
css = urlopen(request, timeout=30).read().decode()
blocks = re.findall(r'/\* latin \*/\s*(@font-face\s*\{[^}]+\})', css) or re.findall(r'@font-face\s*\{[^}]+\}', css)
if not blocks:
    raise RuntimeError('No Latin font subsets were returned.')
saved = {}
styles = []
for block in blocks:
    remote = re.search(r'url\((https[^)]+)\)', block).group(1)
    family = re.search(r"font-family: '([^']+)'", block).group(1).lower().replace(' ', '-')
    if remote not in saved:
        extension = '.woff2' if '.woff2' in remote else '.ttf'
        filename = f'{family}-{len(saved)+1}{extension}'
        (out / filename).write_bytes(urlopen(remote, timeout=30).read())
        saved[remote] = filename
    styles.append(block.replace(remote, '/fonts/' + saved[remote]))
(root / 'frontend/src/fonts.css').write_text('\n'.join(styles) + '\n', encoding='utf-8')
for name, folder in [('outfit','outfit'),('work-sans','worksans')]:
    license_url = f'https://raw.githubusercontent.com/google/fonts/main/ofl/{folder}/OFL.txt'
    (out / f'{name}-OFL.txt').write_bytes(urlopen(license_url, timeout=30).read())
print(f'Saved {len(saved)} local font assets with licenses.')
