#!/usr/bin/env python3
import urllib.request
import json

try:
    resp = urllib.request.urlopen('http://127.0.0.1:11434/api/tags', timeout=5)
    data = json.loads(resp.read())
    models = data.get('models', [])
    print(f'✓ Ollama erreichbar | {len(models)} Modelle:')
    for m in models[:5]:
        name = m.get('name', 'unknown')
        size = m.get('size', 0) / 1e9
        print(f'  - {name}: {size:.1f}GB')
except Exception as e:
    print(f'✗ Fehler: {e}')
