#!/usr/bin/env python3
from pathlib import Path
import re
import json

root = Path(r'C:\Projects\wordpressPlugins')

# Mapping: plugin_dir -> (old_version, new_version)
version_updates = {
    'chimera-benchmark-data': ('1.0.0', '1.0.1'),
    'themisdb-architecture-diagrams': ('1.0.0', '1.0.1'),
    'themisdb-benchmark-visualizer': ('1.0.0', '1.0.1'),
    'themisdb-compendium-downloads': ('1.0.0', '1.0.1'),
    'themisdb-db-backup': ('1.0.1', '1.0.2'),
    'themisdb-docker-downloads': ('1.0.0', '1.0.1'),
    'themisdb-downloads': ('1.0.0', '1.0.1'),
    'themisdb-engagement-tracker': ('1.0.1', '1.0.2'),
    'themisdb-feature-matrix': ('1.0.0', '1.0.1'),
    'themisdb-formula-renderer': ('1.0.0', '1.0.1'),
    'themisdb-front-slider': ('1.1.4', '1.1.5'),
    'themisdb-gallery': ('1.0.0', '1.0.1'),
    'themisdb-github-bridge': ('1.0.0', '1.0.1'),
    'themisdb-graph-navigation': ('1.0.0', '1.0.1'),
    'themisdb-horizon': ('1.0.0', '1.0.1'),
    'themisdb-order-request': ('1.0.0', '1.0.1'),
    'themisdb-persistent-podcast-player': ('1.0.0', '1.0.1'),
    'themisdb-quality-meter': ('1.0.0', '1.0.1'),
    'themisdb-query-playground': ('1.0.0', '1.0.1'),
    'themisdb-release-timeline': ('1.0.0', '1.0.1'),
    'themisdb-support-portal': ('1.0.0', '1.0.1'),
    'themisdb-taxonomy-manager': ('1.0.0', '1.0.1'),
    'themisdb-tco-calculator': ('1.0.0', '1.0.1'),
    'themisdb-test-dashboard': ('1.0.0', '1.0.1'),
    'themisdb-wiki-integration': ('1.0.0', '1.0.1'),
    'wordpress-integration-example': ('1.0.0', '1.0.1'),
}

# Find main plugin files and generate replacements
replacements = []

for plugin_dir, (old_ver, new_ver) in sorted(version_updates.items()):
    # Find main plugin file
    plugin_path = None
    for p in (root / plugin_dir).rglob('*.php'):
        s = str(p)
        if '_backup_' in s or 'vendor' in s or 'node_modules' in s:
            continue
        try:
            text = p.read_text(encoding='utf-8', errors='ignore')
        except:
            continue
        if 'Plugin Name:' in text:
            plugin_path = p
            break
    
    if not plugin_path:
        print(f'SKIP {plugin_dir}: no plugin file found')
        continue
    
    text = plugin_path.read_text(encoding='utf-8', errors='ignore')
    
    # Find the old version string in header with context
    old_header = f' * Version: {old_ver}'
    new_header = f' * Version: {new_ver}'
    
    if old_header not in text:
        print(f'SKIP {plugin_dir}: version {old_ver} not found in header')
        continue
    
    # Find context (3 lines before and after)
    lines = text.split('\n')
    for i, line in enumerate(lines):
        if old_header in line:
            start = max(0, i - 3)
            end = min(len(lines), i + 4)
            old_string = '\n'.join(lines[start:end])
            new_string = old_string.replace(old_header, new_header)
            
            replacements.append({
                'filePath': str(plugin_path),
                'oldString': old_string,
                'newString': new_string
            })
            print(f'OK {plugin_dir}: {old_ver} -> {new_ver}')
            break

print()
print(f'Total replacements: {len(replacements)}')
print()
print('=== JSON FOR multi_replace_string_in_file ===')
print(json.dumps(replacements, indent=2))
