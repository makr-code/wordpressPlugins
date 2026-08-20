#!/usr/bin/env python3
from pathlib import Path
import re

root = Path(r'C:\Projects\wordpressPlugins')
updates = {}

for p in sorted(root.rglob('*.php')):
    s = str(p)
    if '_backup_' in s or 'vendor' in s or 'node_modules' in s:
        continue
    try:
        text = p.read_text(encoding='utf-8', errors='ignore')
    except:
        continue
    if 'Plugin Name:' not in text:
        continue
    
    basename = p.name
    plugin_dir = p.parent.name
    has_update_uri = 'Update URI:' in text
    has_updater_class = 'ThemisDB_Plugin_Updater' in text
    has_activation_hook = 'register_activation_hook' in text
    
    updates[plugin_dir] = {
        'main': basename,
        'update_uri': has_update_uri,
        'updater_class': has_updater_class,
        'activation_hook': has_activation_hook
    }

missing_updates = {k: v for k, v in updates.items() if not v['update_uri']}
with_updater = sum(1 for v in updates.values() if v['updater_class'])
with_hooks = sum(1 for v in updates.values() if v['activation_hook'])

print(f'Total Plugin Entry Files: {len(updates)}')
print(f'Mit Update URI: {sum(1 for v in updates.values() if v["update_uri"])}')
print(f'Mit ThemisDB_Plugin_Updater: {with_updater}')
print(f'Mit Activation Hook: {with_hooks}')
print(f'Fehlendes Update URI: {len(missing_updates)}')
print()

if missing_updates:
    print('Plugins OHNE Update URI:')
    for k in sorted(missing_updates.keys()):
        print(f'  - {k}')
    print()
    print('EMPFEHLUNG: Update URI in den Plugin-Header aufnehmen:')
    print('  * Plugin URI: https://github.com/makr-code/wordpressPlugins')
    print('  * Update URI: https://github.com/makr-code/wordpressPlugins')
else:
    print('✓ PASS: Alle Plugins haben Update URI definiert')

print()
print('Update-Mechanismus-Status:')
for plugin_dir in sorted(updates.keys()):
    v = updates[plugin_dir]
    status_parts = []
    if v['update_uri']:
        status_parts.append('UpdateURI')
    if v['updater_class']:
        status_parts.append('Updater-Klasse')
    if v['activation_hook']:
        status_parts.append('Hooks')
    status = ' + '.join(status_parts) if status_parts else 'KEINE UPDATES'
    print(f'  {plugin_dir}: {status}')
