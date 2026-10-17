#!/usr/bin/env python3
"""
Update all plugin versions to 1.0.1 (minimal version bump)
"""
from pathlib import Path
import re

root = Path(r'C:\Projects\wordpressPlugins')
updated_files = []

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
    
    # Ersetze Version im Plugin-Header
    new_text = re.sub(r'(\* Version: )[0-9.]+', r'\g<1>1.0.1', text)
    
    if new_text != text:
        p.write_text(new_text, encoding='utf-8')
        updated_files.append(plugin_dir)
        print(f'Updated: {plugin_dir}')

print()
print(f'Total files updated: {len(updated_files)}')
