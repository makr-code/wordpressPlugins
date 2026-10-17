#!/usr/bin/env python3
"""Quick-Test der Wikipedia-Link-Funktion"""

import sys
sys.path.insert(0, 'page-content')
from enrich_articles import add_wikipedia_links

# Kurzer Artikel zum Testen
test_markdown = """
# Graph-RAG Analyse

Dies ist eine Analyse von RAG und LLM Technologien.
Wir nutzen Zero Trust Architektur mit Blockchain und Sharding Strategien.
Die Lösung basiert auf Microservices und Cloud Computing Standards.
API-Integration erfolgt über GraphQL und REST Standards.
Verschlüsselung und Authentifizierung sind kritisch.
Skalierbarkeit durch NoSQL und SQL Datenbanken.
"""

print("Eingabe:")
print(test_markdown)
print("\n" + "="*60)
print("Wikipedia-Link-Generierung...")

result = add_wikipedia_links(test_markdown, model='gemma3:4b', timeout=90)

print("\nAusgabe (Wikipedia-Links):")
lines = result.split('\n')
for line in lines:
    if 'wikipedia' in line.lower():
        print(line)

# Zähle Links
link_count = result.count('https://de.wikipedia.org')
print(f"\n✓ {link_count} Wikipedia-Links hinzugefügt")
