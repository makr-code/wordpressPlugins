# Wichtigste LLMs und ihre Fähigkeiten (Stand: Mai 2026)

Diese Übersicht hilft bei der schnellen Modellauswahl für typische Aufgaben in Entwicklung, Recherche, Automatisierung und Content.

Legende:
- OSS: Open-Source oder Open-Weights
- Ollama: In der Praxis lokal via Ollama gut nutzbar

## Kurzvergleich (modernisiert)

| Modellfamilie | OSS | Ollama | Stärken | Schwächen | Typische Einsätze |
|---|---|---|---|---|---|
| OpenAI GPT (GPT-5.x / GPT-4.1-Linie) | nein | nein | Sehr stark in Reasoning, Coding, Tool-Use, strukturierter Ausgabe | Kosten/Latenz je nach Tier | Code-Assistenz, Agenten-Workflows, komplexe Analysen |
| Anthropic Claude (Claude 4.x) | nein | nein | Sehr gute lange Textverarbeitung, saubere Schreibqualität, starke Sicherheitsausrichtung | Tooling je nach Plattform unterschiedlich | Dokumentanalyse, Redaktions-Workflows, Policy-lastige Inhalte |
| Google Gemini (Gemini 2.5-Linie) | nein | nein | Stark multimodal, gute Integration in Google-Ökosystem | Verhalten je nach API/Provider unterschiedlich | Multimodale Extraktion, Wissensarbeit, Assistenzsysteme |
| Llama (Llama 3.3/4 Familie) | ja | ja | Open-Weights, flexibel on-prem/self-hostbar, große Community | Qualität variiert je nach Finetune/Quantisierung | Private Deployments, Forschung, kostensensitive Inferenz |
| Mistral (Large/Mixtral/Small 3) | teils | ja | Gute Effizienz, starkes Preis/Leistungs-Verhältnis | Für Spitzentasks teils hinter Frontier-Modellen | Produktivsysteme mit Fokus auf Kosten und Geschwindigkeit |
| Qwen (Qwen 2.5/3 Familie) | ja | ja | Sehr gute Coding- und Tool-Use-Leistung, mehrsprachig stark | Prompt-/Template-Sensitivität bei lokalem Betrieb | Entwickler-Assistenz, Agenten, Automatisierung |
| DeepSeek (V3/R1 und Distill-Linie) | teils | ja | Stark bei mathematischem/algorithmischem Reasoning | Qualität hängt stark von Distill-Variante ab | Analyse, Code, technische Problemlösung |
| Gemma (Gemma 3/4 IT) | ja | ja | Leichtgewichtig, gut für lokale Nutzung und Experimente | Kleinere Varianten verlieren bei langen/komplexen Inputs | Lokale Assistenten, Edge-Szenarien |
| Phi (Phi-4 Familie) | ja | ja | Sehr gute Effizienz bei kleinem Modell, oft stark in Coding/Reasoning pro Parameter | Bei sehr langen Kontexten limitiert | Lokale Helfer, Batch-Transformation, Copilot-ähnliche Flows |

## Fähigkeits-Matrix (hoch/mittel/basis)

| Fähigkeit | GPT | Claude | Gemini | Llama | Mistral | Qwen | DeepSeek | Gemma |
|---|---|---|---|---|---|---|---|---|
| Coding | hoch | hoch | hoch | mittel-hoch | mittel-hoch | hoch | hoch | mittel |
| Tool-Use/Agentik | hoch | hoch | hoch | mittel | mittel | hoch | hoch | mittel |
| Lange Kontexte | hoch | sehr hoch | hoch | mittel | mittel | hoch | hoch | mittel |
| Multimodalität | hoch | mittel-hoch | sehr hoch | mittel | mittel | mittel-hoch | mittel | mittel |
| On-Prem-Self-Hosting | mittel | niedrig | niedrig-mittel | sehr hoch | hoch | hoch | hoch | sehr hoch |
| Kosten-Effizienz lokal | mittel | niedrig | niedrig-mittel | hoch | hoch | hoch | hoch | sehr hoch |
| Deutschqualität | hoch | hoch | hoch | mittel-hoch | mittel-hoch | hoch | hoch | mittel-hoch |

## Rollen-, Template- und Instruktionsverhalten

Diese Sicht ist entscheidend, wenn dieselbe Prompt-Logik bei verschiedenen APIs/Runnern (z. B. Ollama, Cloud-API, Gateway) laufen soll.

| Modellfamilie | `system`-Rolle | Chat-Template-Sensitivität | Tool-Role/Funktionsaufrufe | Risiko bei langem Input ohne Chunking | Empfehlung für robuste Prompts |
|---|---|---|---|---|---|
| OpenAI GPT | stark unterstützt | mittel | sehr stark | mittel | Klare `system`-Policy + strukturierte `user`-Aufgabe + JSON-Schema |
| Anthropic Claude | stark unterstützt | mittel | stark | mittel | Regeln kurz in `system`, Beispiele in `user`, Ausgabeformat hart vorgeben |
| Google Gemini | unterstützt, je nach API unterschiedlich gewichtet | mittel-hoch | stark | mittel | Rollen sauber nach API-Doku; bei Gateway-Mix Instruktionen zusätzlich im ersten `user`-Turn spiegeln |
| Meta Llama | stark template-abhängig (Runner/Finetune) | hoch | mittel | mittel-hoch | Prompt strikt auf das verwendete Chat-Template abstimmen |
| Mistral | unterstützt, aber template-abhängig | hoch | mittel | mittel-hoch | Bei Self-Hosting explizite Delimiter und kurze, redundante Kernregeln |
| Qwen | meist gut, aber stark vom Chat-Template abhängig | hoch | hoch | mittel | Tool-Schema exakt halten, Kerninstruktionen im ersten `user`-Turn mitführen |
| DeepSeek | gut bei Reasoning, Rollenhandling provider-abhängig | mittel-hoch | hoch | mittel | Explizite Aufgabenstruktur und Checkliste im Prompt, Output streng validieren |
| Gemma (3/4 IT, je nach Runner) | kann eingeschränkt sein; je nach Template wird `system` teils ignoriert | sehr hoch | mittel | hoch bei kleinen quantisierten Varianten | Kernregeln in den ersten `user`-Turn, kleinere Chunks, Few-Shot voranstellen |

## Rollen im LLM-Kontext (kurz)

- `system`: globale Leitplanken und Prioritäten (falls vom Modell/Runner sauber unterstützt).
- `user`: eigentliche Arbeitsanweisung und Inputdaten.
- `assistant` oder `model`: bisherige Antworten/Kontext des Modells.
- `tool`: strukturierte Werkzeugantworten (z. B. Funktionsresultate), die das Modell weiterverarbeitet.

Faustregel: Wenn unklar ist, ob ein Runner `system` korrekt durchreicht, die wichtigsten Regeln immer zusaetzlich in den ersten `user`-Turn schreiben.

## Praktische Auswahl nach Aufgabe

1. Komplexe Code-Änderungen mit Tools: GPT, Claude, Qwen
2. Sehr lange Dokumente redaktionell überarbeiten: Claude, GPT, Gemini
3. Multimodale Pipelines (Bild + Text): Gemini, GPT
4. Lokaler/offline Betrieb: Llama, Gemma, Qwen (quantisiert)
5. Günstige Massenverarbeitung: Mistral, Qwen, DeepSeek
6. Strenge Datenschutz- oder Air-Gap-Anforderungen: Open-Weights-Modelle (Llama, Gemma, Qwen) on-prem

## Universal-Empfehlung fuer eure Zwecke

Fuer eure Pipeline (DOCX -> Markdown, Enrichment, lange Artikel, lokaler Betrieb mit Ollama) ist Qwen die beste Universal-Basis.

Empfohlene Reihenfolge:
1. Qwen (primaer): bester Mix aus Qualitaet, Coding/Strukturtreue und lokaler Verfuegbarkeit.
2. Phi-4 (Fallback fuer Geschwindigkeit): wenn Kosten/Tempo wichtiger als maximale Textqualitaet sind.
3. GPT/Claude (Cloud-Upgrade-Pfad): fuer finale Redaktionsqualitaet bei schwierigen Dokumenten.

Warum Qwen als Universalmodell:
- Stabil bei strukturierten Anweisungen und Reformattierung.
- Gute Tool-Use- und JSON-Disziplin fuer Automatisierungs-Pipelines.
- In Ollama verfuegbar und praktikabel in quantisierten Groessen.
- Besserer Gesamtkompromiss als reine Spezialisten (z. B. nur Reasoning oder nur leichtgewichtig).

## Hinweise für robuste Ergebnisse

- Prompting an Modellformat anpassen (Rollenformat, Chat-Template, Provider-Spezifika).
- Bei Rollenunsicherheit Kerninstruktionen doppeln: kurz in `system`, vollstaendig im ersten `user`-Turn.
- Bei langen Dokumenten immer chunken und anschließend mergen.
- Fuer lokale 4B-8B-Quantisierungen eher 3-4 KB Chunks nutzen statt Volltext in einem Request.
- Für Produktionsqualität: Few-Shot-Beispiele + feste Ausgabeformate (z. B. JSON-Schema).
- Quantisierte kleine Modelle eher für strukturierte Teilaufgaben als für Endredaktion nutzen.
- Vor Modellwechseln mit identischem Testset regressionsprüfen (Qualität, Kosten, Latenz).

Konkreter Startpunkt fuer Ollama lokal:
1. Primaer: Qwen-Instruct in mittlerer bis groesserer Quantisierung.
2. Fallback: Phi-4 fuer schnelle Durchlaeufe.
3. Bei Qualitaetsgrenzen: einzelne kritische Dokumente mit Cloud-Modell nachziehen.

Konkrete 3er-Standardliste (Ollama-Modellnamen):
1. Primary: qwen2.5:14b
2. Quality-Fallback: qwen2.5:7b
3. Speed-Fallback: phi4

Schnellstart:
1. ollama pull qwen2.5:14b
2. ollama pull qwen2.5:7b
3. ollama pull phi4
