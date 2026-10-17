#!/usr/bin/env python3
"""
Erstelle Git-Tags für alle aktiven Plugins mit aktuellen Versionen
Format: plugin-name/vX.Y.Z
"""

from pathlib import Path
import re
import subprocess

root = Path(r'C:\Projects\wordpressPlugins')

# Mapping: plugin_dir -> (old_version, new_version)
version_updates = {
    'chimera-benchmark-data': '1.0.1',
    'themisdb-architecture-diagrams': '1.0.1',
    'themisdb-benchmark-visualizer': '1.0.1',
    'themisdb-compendium-downloads': '1.0.1',
    'themisdb-db-backup': '1.0.2',
    'themisdb-docker-downloads': '1.0.1',
    'themisdb-downloads': '1.0.1',
    'themisdb-engagement-tracker': '1.0.2',
    'themisdb-feature-matrix': '1.0.1',
    'themisdb-formula-renderer': '1.0.1',
    'themisdb-front-slider': '1.1.5',
    'themisdb-gallery': '1.0.1',
    'themisdb-github-bridge': '1.0.1',
    'themisdb-graph-navigation': '1.0.1',
    'themisdb-horizon': '1.0.1',
    'themisdb-order-request': '1.0.1',
    'themisdb-persistent-podcast-player': '1.0.1',
    'themisdb-quality-meter': '1.0.1',
    'themisdb-query-playground': '1.0.1',
    'themisdb-release-timeline': '1.0.1',
    'themisdb-support-portal': '1.0.1',
    'themisdb-taxonomy-manager': '1.0.1',
    'themisdb-tco-calculator': '1.0.1',
    'themisdb-test-dashboard': '1.0.1',
    'themisdb-wiki-integration': '1.0.1',
    'wordpress-integration-example': '1.0.1',
}

print('=== Creating Plugin Tags ===')
print()

# Get current HEAD commit
result = subprocess.run(['git', 'rev-parse', 'HEAD'], capture_output=True, text=True)
current_commit = result.stdout.strip()
print('Current HEAD: {}'.format(current_commit[:8]))
print()

created = 0
skipped = 0

for plugin_dir in sorted(version_updates.keys()):
    version = version_updates[plugin_dir]
    tag_name = '{}/v{}'.format(plugin_dir, version)
    
    # Check if tag already exists
    result = subprocess.run(['git', 'tag', '-l', tag_name], capture_output=True, text=True)
    if result.stdout.strip():
        print('SKIP {}: tag already exists'.format(tag_name))
        skipped += 1
        continue
    
    # Create annotated tag
    message = 'Release {}: version {}'.format(plugin_dir, version)
    try:
        subprocess.run(
            ['git', 'tag', '-a', tag_name, '-m', message],
            check=True,
            capture_output=True
        )
        print('OK   {}'.format(tag_name))
        created += 1
    except subprocess.CalledProcessError as e:
        print('FAIL {}: {}'.format(tag_name, str(e)))

print()
print('Created: {}'.format(created))
print('Skipped: {}'.format(skipped))
print()
print('Next step: git push origin --tags')
