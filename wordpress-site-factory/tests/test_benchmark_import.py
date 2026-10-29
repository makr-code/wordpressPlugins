from __future__ import annotations

import importlib.util
import json
import subprocess
import sys
import pytest
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


def publication_fixture():
    path = MODULE_PATH.parents[2] / "themisdb-benchmark-visualizer" / "tests" / "publication-fixture.json"
    return json.loads(path.read_text(encoding="utf-8"))


def test_publication_metadata_survives_normalization():
    protocol = publication_fixture()
    row = benchmark_import.normalize_benchmark_record({
        "name": "vector-query", "value": 1, "publication": protocol,
    })
    assert row["publication"] == protocol
    assert benchmark_import.publication_errors(row["publication"], "latency") == []


@pytest.mark.parametrize("field,value", [
    ("recall_achieved", 0.4), ("origin", "external_reference"), ("scope", "micro"),
    ("hardware_fingerprint", ""), ("license", ""), ("measured_queries", 0),
    ("concurrency", True), ("version", 2), ("recall_target", float("nan")),
])
def test_incomplete_or_ineligible_protocol_is_not_comparable(field, value):
    protocol = {**publication_fixture(), field: value}
    assert benchmark_import.publication_errors(protocol, "latency")


@pytest.mark.parametrize("value", [None, "bad", float("inf"), float("nan"), -1, True])
def test_invalid_measurements_are_not_silently_zero(value):
    with pytest.raises(ValueError):
        benchmark_import.normalize_benchmark_record({"name": "task", "value": value})


def test_catalog_exposes_publication_issues_without_relabeling_legacy_data(tmp_path):
    path = tmp_path / "bench_vector.json"
    path.write_text(json.dumps({"benchmarks": [
        {"name": "BM_distance/64", "real_time": 1, "time_unit": "ns"},
        {"name": "BM_distance/64_mean", "real_time": 99, "run_type": "aggregate"},
        {"name": "BM_error", "real_time": 99, "error_occurred": True},
    ]}), encoding="utf-8")
    catalog = benchmark_import.build_benchmark_catalog(tmp_path)
    assert len(catalog["records"]) == 1
    assert catalog["publication_contract_version"] == 1
    assert catalog["publication_issues"][0]["errors"] == ["publication metadata missing"]
    assert "publication" not in catalog["records"][0]


def test_bandwidth_is_not_memory():
    assert benchmark_import.normalize_metric_name("bytes_per_second") == "bandwidth"


def test_strict_publication_cli_preserves_existing_output_on_failure(tmp_path):
    source = tmp_path / "source"
    source.mkdir()
    (source / "bench_vector.json").write_text(json.dumps([{"name": "task", "value": 1}]), encoding="utf-8")
    output = tmp_path / "catalog.json"
    output.write_text("existing output", encoding="utf-8")
    result = subprocess.run([
        sys.executable, str(MODULE_PATH), "--source-dir", str(source),
        "--output", str(output), "--require-publication",
    ], capture_output=True, text=True)
    assert result.returncode != 0
    assert "Publication blocked" in result.stderr
    assert output.read_text(encoding="utf-8") == "existing output"


def test_strict_publication_cli_accepts_complete_fixture(tmp_path):
    source = tmp_path / "source"
    source.mkdir()
    (source / "bench_vector.json").write_text(json.dumps([{
        "name": "vector-query", "value": 1, "workload": "vector_search",
        "publication": publication_fixture(),
    }]), encoding="utf-8")
    output = tmp_path / "catalog.json"
    result = subprocess.run([
        sys.executable, str(MODULE_PATH), "--source-dir", str(source),
        "--output", str(output), "--require-publication",
    ], capture_output=True, text=True)
    assert result.returncode == 0, result.stderr
    assert json.loads(output.read_text(encoding="utf-8"))["publication_issues"] == []


def test_metric_statistic_and_workload_are_part_of_publication_validation():
    protocol = publication_fixture()
    assert benchmark_import.publication_errors(protocol, "throughput")
    assert benchmark_import.publication_errors(protocol, "latency", "all")
    assert benchmark_import.publication_errors({**protocol, "top_k": 1001}, "latency")


def test_legacy_flowchart_does_not_invent_a_winner():
    graph = benchmark_import.build_mermaid_flowchart([
        {"engine": "ThemisDB", "metric_value": 1},
        {"engine": "PostgreSQL", "metric_value": 2},
    ])
    assert "Winner" not in graph
    assert "n0 --> n1" not in graph
