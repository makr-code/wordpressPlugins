#!/usr/bin/env python3
from pathlib import Path
import re

root = Path(r'C:\Projects\wordpressPlugins')
versions = {}

for p in sorted(root.rglob('*.php')):
    s = str(p)
    if '_backup_' in s or 'vendor' in s or 'node_modules' in s or '.git' in s:
        continue
    try:
        text = p.read_text(encoding='utf-8', errors='ignore')
    except:
        continue
    if 'Plugin Name:' not in text:
        continue
    
    plugin_dir = p.parent.name
    if plugin_dir == 'page-content':
        continue
    
    # Finde Version im Header
    m = re.search(r'\* Version: ([0-9.]+)', text)
    if m:
        versions[plugin_dir] = m.group(1)
    else:
        versions[plugin_dir] = 'MISSING'

print('AKTUELLE VERSIONEN:')
print()
for k in sorted(versions.keys()):
    print(f'{k:40} {versions[k]}')

print()
count_1_0_0 = sum(1 for v in versions.values() if v == '1.0.0')
count_other = sum(1 for v in versions.values() if v != '1.0.0' and v != 'MISSING')
print(f'Plugins mit 1.0.0: {count_1_0_0}')
print(f'Plugins mit anderen Versionen: {count_other}')
