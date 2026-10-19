from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


def test_site_factory_documents_greenfield_target_model():
    architecture = (ROOT / "ARCHITECTURE.md").read_text(encoding="utf-8")
    assert "# ThemisDB WordPress Site Factory" in architecture
    assert "Zielmodell und Migrations-Contract" in architecture
    assert "## 1. Ziel und Zweck" in architecture
    assert "themisdb-pulse" in architecture
    assert "Greenfield-Setup" in architecture
    assert "Live-Differenz" in architecture


def test_reserved_taxonomy_contract_matches_factory_config():
    taxonomy_cfg = json.loads((ROOT / "config" / "taxonomy.json").read_text(encoding="utf-8"))

    assert "selection_tags" in taxonomy_cfg
    assert "restricted_tags" in taxonomy_cfg
    assert "descriptive_tags" in taxonomy_cfg

    selection_tags = taxonomy_cfg["selection_tags"]
    restricted_tags = taxonomy_cfg["restricted_tags"]
    descriptive_tags = taxonomy_cfg["descriptive_tags"]

    assert isinstance(selection_tags, list)
    assert isinstance(restricted_tags, list)
    assert isinstance(descriptive_tags, list)

    expected_selection = {
        "announcement",
        "hero",
        "feature",
        "documentation",
        "pricing",
        "download",
        "edition",
        "blog",
        "support",
        "recommended",
        "lesenswert",
        "editor-pick",
        "release",
        "event",
        "conference",
        "security",
        "benchmark",
        "case-study",
        "trial",
    }
    expected_restricted = {"restricted", "internal", "admin-only"}
    expected_descriptive = {
        "architecture",
        "webinar",
        "docker",
        "binary",
        "package",
        "compendium",
        "diagram",
        "roadmap",
        "changelog",
        "community",
        "demo",
        "how-to",
        "guide",
        "customer-story",
        "support-portal",
        "ticket",
        "knowledge-base",
        "helpdesk",
        "api",
        "reference",
        "runbook",
        "audit",
        "compliance",
        "performance",
        "testing",
        "governance",
        "analysis",
        "research",
        "air-gap",
        "ddil",
        "local-ai",
        "sovereignty",
        "data-sovereignty",
        "zero-trust",
        "on-premise",
        "self-hosted",
        "mission-critical",
        "incident-response",
        "operational-resilience",
        "resilience",
    }

    selection_set = set(selection_tags)
    restricted_set = set(restricted_tags)
    descriptive_set = set(descriptive_tags)

    assert len(selection_tags) == len(selection_set), "Duplicate tags found in selection_tags"
    assert len(restricted_tags) == len(restricted_set), "Duplicate tags found in restricted_tags"
    assert len(descriptive_tags) == len(descriptive_set), "Duplicate tags found in descriptive_tags"
    assert selection_set.isdisjoint(restricted_set)
    assert restricted_set.isdisjoint(descriptive_set)
    assert selection_set.isdisjoint(descriptive_set)
    assert selection_set == expected_selection
    assert restricted_set == expected_restricted
    assert descriptive_set == expected_descriptive
