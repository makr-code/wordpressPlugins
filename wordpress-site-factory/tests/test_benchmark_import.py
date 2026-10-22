from __future__ import annotations

import importlib.util
from pathlib import Path

MODULE_PATH = Path(__file__).resolve().parents[1] / "importers" / "benchmark_import.py"

spec = importlib.util.spec_from_file_location("benchmark_import", MODULE_PATH)
benchmark_import = importlib.util.module_from_spec(spec)
assert spec is not None and spec.loader is not None
spec.loader.exec_module(benchmark_import)


def test_normalize_benchmark_record_handles_aliases():
    record = benchmark_import.normalize_benchmark_record({
        "benchmark": "vector-lookup",
        "database": "ThemisDB",
        "metric": "latency_ms",
        "value": 12.5,
        "category": "vector_search",
        "dataset": "1m-vecs",
    })

    assert record["benchmark_name"] == "vector-lookup"
    assert record["engine"] == "ThemisDB"
    assert record["metric_name"] == "latency"
    assert record["metric_unit"] == "ms"
    assert record["metric_value"] == 12.5


def test_build_mermaid_flowchart_uses_compared_engines():
    rows = [
        {"engine": "ThemisDB", "metric_name": "latency", "metric_value": 12.5},
        {"engine": "PostgreSQL", "metric_name": "latency", "metric_value": 42.0},
        {"engine": "MongoDB", "metric_name": "latency", "metric_value": 86.0},
    ]

    graph = benchmark_import.build_mermaid_flowchart(rows, metric="latency", category="vector_search")
    assert "flowchart LR" in graph
    assert "ThemisDB" in graph
    assert "PostgreSQL" in graph
    assert "MongoDB" in graph


def test_normalize_benchmark_dataset_groups_by_engine_and_mermaid():
    rows = [
        {"engine": "ThemisDB", "metric_name": "latency", "metric_value": 10, "category": "vector_search"},
        {"engine": "ThemisDB", "metric_name": "latency", "metric_value": 15, "category": "vector_search"},
        {"engine": "PostgreSQL", "metric_name": "latency", "metric_value": 40, "category": "vector_search"},
    ]

    dataset = benchmark_import.normalize_benchmark_dataset(rows, metric="latency", category="vector_search")
    assert dataset["metric"] == "latency"
    assert "mermaid" in dataset
    assert "flowchart LR" in dataset["mermaid"]
    assert dataset["labels"][0] in {"ThemisDB", "PostgreSQL"}


def test_load_benchmark_rows_parses_google_benchmark_json(tmp_path):
    file_path = tmp_path / "bench_tpcc_latest.json"
    file_path.write_text(
        """{
  "context": {"date": "2026-05-10T18:00:42+02:00"},
  "benchmarks": [
    {
      "name": "TPCCLiteFixture/NewOrderTransaction/1/100",
      "threads": 1,
      "real_time": 0.5,
      "cpu_time": 0.4,
      "time_unit": "ms",
      "items_per_second": 2126.2
    }
  ]
}""",
        encoding="utf-8",
    )
    rows = benchmark_import.load_benchmark_rows(file_path)
    metric_names = {row["metric_name"] for row in rows}
    assert "latency" in metric_names
    assert "throughput" in metric_names
    assert all(row["source_file"] == "bench_tpcc_latest.json" for row in rows)


def test_build_benchmark_catalog_skips_invalid_json(tmp_path):
    valid_file = tmp_path / "bench_query.smoke.json"
    valid_file.write_text(
        """{
  "context": {"date": "2026-05-10T18:00:42+02:00"},
  "benchmarks": [{"name":"QueryFixture/FastPath","real_time":1500,"time_unit":"us"}]
}""",
        encoding="utf-8",
    )
    invalid_file = tmp_path / "bench_broken.smoke.json"
    invalid_file.write_text("{ invalid json", encoding="utf-8")

    catalog = benchmark_import.build_benchmark_catalog(tmp_path)
    assert catalog["stats"]["total_files"] == 2
    assert catalog["stats"]["failed_files"] == 1
    assert catalog["stats"]["total_records"] >= 1
    assert any(item["file_name"] == "bench_broken.smoke.json" for item in catalog["errors"])
