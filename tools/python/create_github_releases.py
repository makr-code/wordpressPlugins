#!/usr/bin/env python3
"""
Erstelle GitHub Releases für alle Plugin-Tags
Nutzt GitHub CLI (gh) für die Erstellung
"""

import subprocess
import re
from pathlib import Path

root = Path(r'C:\Projects\wordpressPlugins')

# Mapping: plugin_dir -> (current_version, previous_version)
plugin_info = {
    'chimera-benchmark-data': ('1.0.1', '1.0.0'),
    'themisdb-architecture-diagrams': ('1.0.1', '1.0.0'),
    'themisdb-benchmark-visualizer': ('1.0.1', '1.0.0'),
    'themisdb-compendium-downloads': ('1.0.1', '1.0.0'),
    'themisdb-db-backup': ('1.0.2', '1.0.1'),
    'themisdb-docker-downloads': ('1.0.1', '1.0.0'),
    'themisdb-downloads': ('1.0.1', '1.0.0'),
    'themisdb-engagement-tracker': ('1.0.2', '1.0.1'),
    'themisdb-feature-matrix': ('1.0.1', '1.0.0'),
    'themisdb-formula-renderer': ('1.0.1', '1.0.0'),
    'themisdb-front-slider': ('1.1.5', '1.1.4'),
    'themisdb-gallery': ('1.0.1', '1.0.0'),
    'themisdb-github-bridge': ('1.0.1', '1.0.0'),
    'themisdb-graph-navigation': ('1.0.1', '1.0.0'),
    'themisdb-horizon': ('1.0.1', '1.0.0'),
    'themisdb-order-request': ('1.0.1', '1.0.0'),
    'themisdb-persistent-podcast-player': ('1.0.1', '1.0.0'),
    'themisdb-quality-meter': ('1.0.1', '1.0.0'),
    'themisdb-query-playground': ('1.0.1', '1.0.0'),
    'themisdb-release-timeline': ('1.0.1', '1.0.0'),
    'themisdb-support-portal': ('1.0.1', '1.0.0'),
    'themisdb-taxonomy-manager': ('1.0.1', '1.0.0'),
    'themisdb-tco-calculator': ('1.0.1', '1.0.0'),
    'themisdb-test-dashboard': ('1.0.1', '1.0.0'),
    'themisdb-wiki-integration': ('1.0.1', '1.0.0'),
    'wordpress-integration-example': ('1.0.1', '1.0.0'),
}

def get_plugin_description(plugin_dir):
    """Extrahiere die Plugin-Beschreibung."""
    for p in (root / plugin_dir).rglob('*.php'):
        if '_backup_' in str(p) or 'vendor' in str(p) or 'node_modules' in str(p):
            continue
        try:
            text = p.read_text(encoding='utf-8', errors='ignore')
        except:
            continue
        
        if 'Plugin Name:' not in text:
            continue
        
        match = re.search(r'\* Description: (.+)', text)
        if match:
            return match.group(1).strip()
    
    return 'WordPress Plugin'

print('=== Creating GitHub Releases ===')
print()

created = 0
failed = 0

for plugin_dir in sorted(plugin_info.keys()):
    version, prev_version = plugin_info[plugin_dir]
    tag_name = '{}/v{}'.format(plugin_dir, version)
    
    # Check if release already exists
    result = subprocess.run(
        ['gh', 'release', 'view', tag_name],
        capture_output=True,
        text=True
    )
    
    if result.returncode == 0:
        print('SKIP {}: release already exists'.format(tag_name))
        continue
    
    description = get_plugin_description(plugin_dir)
    
    # Create release notes
    release_notes = '## {}\n\n'.format(plugin_dir.replace('-', ' ').title())
    release_notes += 'Version: **v{}**\n\n'.format(version)
    release_notes += '### Description\n'
    release_notes += description + '\n\n'
    release_notes += '### Installation\n'
    release_notes += '1. Download the plugin from this release\n'
    release_notes += '2. Upload to wp-content/plugins/\n'
    release_notes += '3. Activate in WordPress Admin\n\n'
    release_notes += '### Compatibility\n'
    release_notes += '- WordPress: 5.0+\n'
    release_notes += '- PHP: 7.4+\n\n'
    release_notes += '### GitHub\n'
    release_notes += 'https://github.com/makr-code/wordpressPlugins'
    
    # Create release
    try:
        subprocess.run(
            ['gh', 'release', 'create', tag_name, '--title', 'Release v{}'.format(version), 
             '--notes', release_notes],
            check=True,
            capture_output=True
        )
        print('OK   {}'.format(tag_name))
        created += 1
    except subprocess.CalledProcessError as e:
        print('FAIL {}: {}'.format(tag_name, str(e)))
        failed += 1

print()
print('Created: {}'.format(created))
print('Failed: {}'.format(failed))
print()
print('GitHub Releases are now available for all plugins!')
