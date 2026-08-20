#!/usr/bin/env python3
from pathlib import Path
import re

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

root = Path(r'C:\Projects\wordpressPlugins')
print('=== VERSION-UPDATE-PLAN ===')
print()

for plugin_dir, (old_ver, new_ver) in sorted(version_updates.items()):
    print(f'{plugin_dir}: {old_ver} -> {new_ver}')

print()
print('=== Replacements generiert ===')
print(f'Total: {len(version_updates)} plugins')
