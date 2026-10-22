from __future__ import annotations

import importlib.util
import json
from pathlib import Path

MODULE_PATH = Path(__file__).resolve().parents[1] / "importers" / "benchmark_docs_validate.py"

spec = importlib.util.spec_from_file_location("benchmark_docs_validate", MODULE_PATH)
benchmark_docs_validate = importlib.util.module_from_spec(spec)
assert spec is not None and spec.loader is not None
spec.loader.exec_module(benchmark_docs_validate)


def test_validate_benchmark_docs_counts_wp_and_data_alignment(tmp_path):
    docs_root = tmp_path / "docs"
    docs_root.mkdir(parents=True, exist_ok=True)
    good_doc = docs_root / "GOOD.md"
    good_doc.write_text(
        "# Good\n\n"
        "Throughput: 1000 ops/s\n"
        "Latency: 50 ms\n"
        "Source: bench_query.smoke.json\n"
        "Canonical KPI: PERFORMANCE_EXPECTATIONS.md\n",
        encoding="utf-8",
    )
    bad_doc = docs_root / "BAD.md"
    bad_doc.write_text(
        "# Bad\n\n"
        "Latency should be 99 ms\n"
        "Source: bench_missing.json\n"
        "See PERFORMANCE_EXPECTATION.md\n",
        encoding="utf-8",
    )
    expectations_dir = tmp_path / "src" / "module-a"
    expectations_dir.mkdir(parents=True, exist_ok=True)
    (expectations_dir / "PERFORMANCE_EXPECTATIONS.md").write_text("# canonical\n", encoding="utf-8")

    catalog_path = tmp_path / "benchmark_catalog.json"
    catalog_path.write_text(
        json.dumps(
            {
                "files": [
                    {"file_name": "bench_query.smoke.json", "parse_status": "ok", "records_count": 2},
                    {"file_name": "bench_invalid.json", "parse_status": "invalid", "records_count": 0},
                ],
                "records": [
                    {
                        "source_file": "bench_query.smoke.json",
                        "metric_unit": "ops/s",
                        "metric_value": 1000,
                    },
                    {
                        "source_file": "bench_query.smoke.json",
                        "metric_unit": "ms",
                        "metric_value": 50,
                    },
                ],
            },
            ensure_ascii=False,
        ),
        encoding="utf-8",
    )

    def fake_wp_content_loader(doc):
        slug = str(doc.get("slug") or "")
        if slug == "good":
            return "<p>Throughput: 1000 ops/s</p><p>Latency: 50 ms</p><p>Source: bench_query.smoke.json</p>"
        if slug == "bad":
            return "<p>Latency should be 101 ms</p><p>Source: bench_missing.json</p>"
        return None

    report = benchmark_docs_validate.validate_benchmark_docs(
        docs_root=docs_root,
        catalog_path=catalog_path,
        base_url="http://localhost/wordpress",
        user="",
        password="",
        repo_root=tmp_path,
        wp_content_loader=fake_wp_content_loader,
    )

    assert report["docs_total"] == 2
    assert report["wp_docs_found"] == 2
    assert report["docs_with_related_files"] == 2
    assert report["docs_missing_related_files"] == 1
    assert report["docs_with_data_backing"] == 1
    assert report["docs_with_data_numeric_claims"] == 1
    assert report["docs_with_supported_data_claims"] == 1
    assert report["docs_with_fully_supported_data_claims"] == 1
    assert report["docs_with_unsupported_data_claims"] == 0
    assert report["docs_with_expectation_refs"] == 2
    assert report["docs_with_missing_expectation_refs"] == 1
    assert report["docs_with_wp_numeric_mismatch"] == 1
    assert report["docs_with_wp_file_ref_mismatch"] == 0
    assert "category_comparability" in report
    assert "comparability_score_global" in report
    assert report["ctcb_evolution"]["status"] == "no-baseline"


def test_validate_benchmark_docs_builds_ctcb_evolution(tmp_path):
    docs_root = tmp_path / "docs"
    docs_root.mkdir(parents=True, exist_ok=True)
    (docs_root / "ONE.md").write_text(
        "# One\n\nLatency: 10 ms\nSource: bench_query.smoke.json\n",
        encoding="utf-8",
    )
    catalog_path = tmp_path / "benchmark_catalog.json"
    catalog_path.write_text(
        json.dumps(
            {
                "files": [{"file_name": "bench_query.smoke.json", "parse_status": "ok", "records_count": 2}],
                "records": [
                    {"source_file": "bench_query.smoke.json", "metric_unit": "ms", "metric_name": "latency", "metric_value": 10, "benchmark_name": "CaseA", "engine": "ThemisDB", "workload": "advanced"},
                    {"source_file": "bench_query.smoke.json", "metric_unit": "ms", "metric_name": "latency", "metric_value": 11, "benchmark_name": "CaseA", "engine": "PostgreSQL", "workload": "advanced"},
                ],
            },
            ensure_ascii=False,
        ),
        encoding="utf-8",
    )
    previous_report = tmp_path / "previous.json"
    previous_report.write_text(
        json.dumps(
            {
                "numeric_token_match_rate": 90.0,
                "docs_with_missing_expectation_refs": 1,
                "docs_with_unsupported_data_claims": 1,
                "docs_missing_related_files": 1,
                "docs_with_data_backing": 0,
                "docs_with_wp_numeric_mismatch": 1,
                "comparability_score_global": 10.0,
            }
        ),
        encoding="utf-8",
    )

    def fake_wp_content_loader(doc):
        return "<p>Latency: 10 ms</p><p>Source: bench_query.smoke.json</p>"

    report = benchmark_docs_validate.validate_benchmark_docs(
        docs_root=docs_root,
        catalog_path=catalog_path,
        base_url="http://localhost/wordpress",
        user="",
        password="",
        repo_root=tmp_path,
        previous_report=previous_report,
        wp_content_loader=fake_wp_content_loader,
    )

    assert report["ctcb_evolution"]["status"] == "compared"
    assert report["ctcb_evolution"]["trend"] == "improving"
    assert report["ctcb_evolution"]["deltas"]["docs_with_data_backing"]["delta"] == 1.0
    assert report["ctcb_evolution"]["deltas"]["comparability_score_global"]["delta"] == 90.0
