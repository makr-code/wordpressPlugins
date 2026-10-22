from __future__ import annotations

import importlib.util
from pathlib import Path

MODULE_PATH = Path(__file__).resolve().parents[1] / "importers" / "benchmark_docs_manifest.py"

spec = importlib.util.spec_from_file_location("benchmark_docs_manifest", MODULE_PATH)
benchmark_docs_manifest = importlib.util.module_from_spec(spec)
assert spec is not None and spec.loader is not None
spec.loader.exec_module(benchmark_docs_manifest)


def test_build_benchmark_docs_manifest_extracts_metadata(tmp_path):
    expectations_dir = tmp_path / "src" / "module-a"
    expectations_dir.mkdir(parents=True, exist_ok=True)
    (expectations_dir / "PERFORMANCE_EXPECTATIONS.md").write_text("# Expectations\n", encoding="utf-8")

    doc = tmp_path / "BENCHMARK_IMPLEMENTATION_REPORT.md"
    doc.write_text(
        "# Benchmark Implementation Report\n\n"
        "Status: Historical snapshot\n"
        "Related files: bench_query.smoke.json and benchmark_results.json\n"
        "Canonical KPI: PERFORMANCE_EXPECTATIONS.md\n",
        encoding="utf-8",
    )

    manifest = benchmark_docs_manifest.build_benchmark_docs_manifest(tmp_path, repo_root=tmp_path)
    assert manifest["document_count"] == 1
    assert manifest["historical_count"] == 1
    assert manifest["expectation_reference_count"] == 1
    assert manifest["expectation_docs_discovered"] == 1
    entry = manifest["documents"][0]
    assert entry["title"] == "Benchmark Implementation Report"
    assert entry["metadata"]["is_historical"] is True
    assert "bench_query.smoke.json" in entry["metadata"]["related_benchmark_files"]
    assert "benchmark_results.json" in entry["metadata"]["related_benchmark_files"]
    assert "PERFORMANCE_EXPECTATIONS.md" in entry["metadata"]["related_expectation_docs"]
    assert entry["metadata"]["missing_expectation_refs"] == []
