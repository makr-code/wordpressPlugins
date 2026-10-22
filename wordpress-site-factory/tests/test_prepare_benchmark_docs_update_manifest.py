from __future__ import annotations

import importlib.util
import json
from pathlib import Path

MODULE_PATH = Path(__file__).resolve().parents[1] / "importers" / "prepare_benchmark_docs_update_manifest.py"

spec = importlib.util.spec_from_file_location("prepare_benchmark_docs_update_manifest", MODULE_PATH)
prepare_manifest = importlib.util.module_from_spec(spec)
assert spec is not None and spec.loader is not None
spec.loader.exec_module(prepare_manifest)


def test_build_existing_only_manifest_filters_documents(tmp_path, monkeypatch):
    source_manifest = tmp_path / "benchmark_docs_cache.json"
    source_manifest.write_text(
        json.dumps(
            {
                "documents": [
                    {"title": "Doc A", "slug": "doc-a", "source_path": "a.md"},
                    {"title": "Doc B", "slug": "doc-b", "source_path": "b.md"},
                ]
            },
            ensure_ascii=False,
        ),
        encoding="utf-8",
    )
    output_manifest = tmp_path / "benchmark_docs_existing_cache.json"

    def fake_existing_wp_doc(base_url, user, password, source_path, slug, title):
        if slug == "doc-a":
            return {"id": 10, "slug": "doc-a"}
        return None

    monkeypatch.setattr(prepare_manifest, "existing_wp_doc", fake_existing_wp_doc)
    result = prepare_manifest.build_existing_only_manifest(
        manifest_path=source_manifest,
        output_path=output_manifest,
        base_url="https://example.org",
        user="u",
        password="p",
    )

    assert result["existing_documents"] == 1
    assert result["missing_documents"] == 1
    assert output_manifest.exists()
