#!/usr/bin/env python3
from pathlib import Path

root = Path(r'C:\Projects\wordpressPlugins')

# Special version updates
special_updates = {
    'themisdb-db-backup': ('1.0.1', '1.0.2'),
    'themisdb-engagement-tracker': ('1.0.1', '1.0.2'),
    'themisdb-front-slider': ('1.1.4', '1.1.5'),
}

for plugin_dir, (old_ver, new_ver) in special_updates.items():
    plugin_path = root / plugin_dir
    if not plugin_path.exists():
        print('SKIP {}: not found'.format(plugin_dir))
        continue
    
    for p in plugin_path.rglob('*.php'):
        if '_backup_' in str(p) or 'vendor' in str(p) or 'node_modules' in str(p):
            continue
        try:
            text = p.read_text(encoding='utf-8', errors='ignore')
        except:
            continue
        if 'Plugin Name:' not in text:
            continue
        
        new_text = text.replace(' * Version: ' + old_ver, ' * Version: ' + new_ver)
        new_text = new_text.replace("'" + old_ver + "'", "'" + new_ver + "'")
        new_text = new_text.replace('"' + old_ver + '"', '"' + new_ver + '"')
        
        if new_text != text:
            p.write_text(new_text, encoding='utf-8')
            print('OK {}: {} -> {}'.format(plugin_dir, old_ver, new_ver))
        break

print()
print('Done')
