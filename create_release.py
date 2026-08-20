#!/usr/bin/env python3
"""
Automatisiertes Release-Skript für wordpressPlugins

Dieses Skript:
1. Sammelt alle aktuellen Plugin-Versionen
2. Generiert Release-Notizen
3. Erstellt ein Git-Tag
4. Pusht zu GitHub
"""

from pathlib import Path
from datetime import datetime
import subprocess
import sys
import re

def get_all_versions():
    """Sammle alle Plugin-Versionen."""
    root = Path(r'C:\Projects\wordpressPlugins')
    versions = {}
    
    for p in sorted(root.rglob('*.php')):
        if '_backup_' in str(p) or 'vendor' in str(p) or 'node_modules' in str(p) or '.git' in str(p):
            continue
        try:
            text = p.read_text(encoding='utf-8', errors='ignore')
        except:
            continue
        
        if 'Plugin Name:' not in text:
            continue
        
        # Extract plugin name and version
        name_match = re.search(r'\* Plugin Name: (.+)', text)
        version_match = re.search(r'\* Version: ([\d.]+)', text)
        
        if name_match and version_match:
            plugin_name = name_match.group(1).strip()
            version = version_match.group(1)
            plugin_dir = p.parent.name
            versions[plugin_dir] = {
                'name': plugin_name,
                'version': version,
                'file': str(p)
            }
    
    return versions

def generate_release_notes(versions):
    """Generiere Release-Notizen aus den Versionen."""
    today = datetime.now().strftime('%Y-%m-%d')
    
    notes = f'## Release {today}\n\n'
    notes += '### Plugin Updates\n\n'
    
    for plugin_dir in sorted(versions.keys()):
        v = versions[plugin_dir]
        notes += f'- **{v["name"]}**: v{v["version"]}\n'
    
    notes += f'\n### Total Plugins: {len(versions)}\n'
    notes += '\nAll plugins support automatic WordPress updates.\n'
    
    return notes

def get_git_tag_version():
    """Bestimme die nächste Tag-Version."""
    try:
        # Get latest tag
        result = subprocess.run(
            ['git', 'describe', '--tags', '--abbrev=0'],
            capture_output=True,
            text=True
        )
        
        if result.returncode == 0:
            last_tag = result.stdout.strip()
            # Parse version
            match = re.match(r'v(\d+)\.(\d+)\.(\d+)', last_tag)
            if match:
                major, minor, patch = int(match.group(1)), int(match.group(2)), int(match.group(3))
                # Increment patch
                new_patch = patch + 1
                return f'v{major}.{minor}.{new_patch}'
        
        # No tags found, start with v1.0.0
        return 'v1.0.0'
    except:
        return 'v1.0.0'

def create_git_tag(tag, message):
    """Erstelle ein Git-Tag."""
    try:
        subprocess.run(['git', 'tag', '-a', tag, '-m', message], check=True)
        print(f'✓ Tag created: {tag}')
        return True
    except subprocess.CalledProcessError as e:
        print(f'✗ Tag creation failed: {e}')
        return False

def push_to_github(tag):
    """Pushe das Tag zu GitHub."""
    try:
        subprocess.run(['git', 'push', 'origin', tag], check=True)
        print(f'✓ Tag pushed to GitHub: {tag}')
        return True
    except subprocess.CalledProcessError as e:
        print(f'✗ Push failed: {e}')
        return False

def main():
    print('=== wordpressPlugins Release Generator ===')
    print()
    
    # Sammle Versionen
    print('1. Collecting plugin versions...')
    versions = get_all_versions()
    print(f'   Found {len(versions)} plugins')
    print()
    
    # Generiere Release-Notizen
    print('2. Generating release notes...')
    release_notes = generate_release_notes(versions)
    print(release_notes)
    print()
    
    # Bestimme nächste Tag-Version
    print('3. Determining next version...')
    next_tag = get_git_tag_version()
    print(f'   Next tag: {next_tag}')
    print()
    
    # Bestätige Release
    confirm = input('Create release with tag {}? (y/n): '.format(next_tag))
    if confirm.lower() != 'y':
        print('Release cancelled.')
        return
    
    # Erstelle Git-Tag
    print()
    print('4. Creating Git tag...')
    if not create_git_tag(next_tag, 'Release {}'.format(next_tag)):
        return
    
    # Pushe zu GitHub
    print()
    print('5. Pushing to GitHub...')
    if not push_to_github(next_tag):
        print('Note: Tag created locally but push failed.')
        print('Push manually with: git push origin {}'.format(next_tag))
        return
    
    print()
    print('=== Release Successfully Created ===')
    print(f'Tag: {next_tag}')
    print('GitHub Release will be created automatically.')
    print()
    print('View the release at:')
    print(f'https://github.com/makr-code/wordpressPlugins/releases/tag/{next_tag}')

if __name__ == '__main__':
    main()
