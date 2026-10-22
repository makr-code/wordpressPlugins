from __future__ import annotations

import argparse
import csv
import json
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, Iterable, List, Mapping, Optional, Sequence


def normalize_metric_name(raw: Any) -> str:
    value = str(raw or "").strip().lower()
    aliases = {
        "latency": "latency",
        "latency_ms": "latency",
        "throughput": "throughput",
        "throughput_ops": "throughput",
        "ops_sec": "throughput",
        "qps": "throughput",
        "memory": "memory",
        "memory_mb": "memory",
        "bytes_per_second": "memory",
    }
    return aliases.get(value, value or "latency")


def _safe_float(value: Any, default: float = 0.0) -> float:
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def _to_latency_ms(value: Any, unit: Any) -> float:
    raw = _safe_float(value, default=0.0)
    normalized_unit = str(unit or "ms").strip().lower()
    if normalized_unit == "ns":
        return raw / 1_000_000.0
    if normalized_unit == "us":
        return raw / 1_000.0
    if normalized_unit == "s":
        return raw * 1_000.0
    return raw


def infer_category_from_name(name: str) -> str:
    text = str(name or "").strip().lower()
    mapping = {
        "vector_search": ("vector", "embedding", "hnsw", "simd_distance"),
        "graph_traversal": ("graph", "pagerank", "traversal"),
        "transaction": ("tpcc", "transaction", "ycsb", "mvcc", "lock"),
        "compression": ("compression", "zstd", "gorilla", "codec"),
        "advanced": ("aql", "changefeed", "async", "batch", "querymix"),
        "content": ("content", "blob", "branch", "document"),
        "gpu": ("gpu", "cuda", "rocm", "vulkan"),
    }
    for category, needles in mapping.items():
        if any(needle in text for needle in needles):
            return category
    return "all"


def normalize_benchmark_record(raw: Mapping[str, Any]) -> Dict[str, Any]:
    record = {str(key): value for key, value in raw.items() if value is not None}

    benchmark_name = str(record.get("benchmark_name") or record.get("benchmark") or record.get("name") or "benchmark").strip() or "benchmark"
    workload = str(record.get("workload") or record.get("category") or infer_category_from_name(benchmark_name)).strip() or "all"
    metric_name = normalize_metric_name(record.get("metric_name") or record.get("metric") or "latency")
    metric_unit = str(record.get("metric_unit") or record.get("unit") or {
        "latency": "ms",
        "throughput": "ops/s",
        "memory": "MB",
    }.get(metric_name, "")).strip()

    metric_value = record.get("metric_value")
    if metric_value is None:
        metric_value = record.get("value")
    metric_value = _safe_float(metric_value, default=0.0)

    return {
        "benchmark_name": benchmark_name,
        "workload": workload,
        "dataset_size": str(record.get("dataset_size") or record.get("dataset") or record.get("scale") or "n/a").strip() or "n/a",
        "engine": str(record.get("engine") or record.get("database") or record.get("db") or "ThemisDB").strip() or "ThemisDB",
        "environment": str(record.get("environment") or record.get("target") or "").strip(),
        "metric_name": metric_name,
        "metric_unit": metric_unit,
        "metric_value": metric_value,
        "notes": str(record.get("notes") or record.get("description") or "").strip(),
        "source_file": str(record.get("source_file") or "").strip(),
        "threads": int(_safe_float(record.get("threads"), default=0)),
        "time_unit": str(record.get("time_unit") or "").strip(),
    }


def _google_benchmark_records(path: Path, payload: Mapping[str, Any]) -> List[Dict[str, Any]]:
    benchmarks = payload.get("benchmarks")
    if not isinstance(benchmarks, list):
        return []

    category = infer_category_from_name(path.stem)
    records: List[Dict[str, Any]] = []
    for bench in benchmarks:
        if not isinstance(bench, Mapping):
            continue
        benchmark_name = str(bench.get("name") or bench.get("run_name") or path.stem).strip() or path.stem
        threads = int(_safe_float(bench.get("threads"), default=0))
        time_unit = str(bench.get("time_unit") or "ms").strip()

        latency_source = bench.get("real_time")
        if latency_source is None:
            latency_source = bench.get("cpu_time")
        if latency_source is not None:
            records.append(
                normalize_benchmark_record(
                    {
                        "benchmark_name": benchmark_name,
                        "workload": category,
                        "engine": "ThemisDB",
                        "metric_name": "latency",
                        "metric_unit": "ms",
                        "metric_value": _to_latency_ms(latency_source, time_unit),
                        "source_file": path.name,
                        "threads": threads,
                        "time_unit": time_unit,
                    }
                )
            )

        throughput = bench.get("items_per_second")
        if throughput is None:
            throughput = bench.get("qps_est")
        if throughput is not None:
            records.append(
                normalize_benchmark_record(
                    {
                        "benchmark_name": benchmark_name,
                        "workload": category,
                        "engine": "ThemisDB",
                        "metric_name": "throughput",
                        "metric_unit": "ops/s",
                        "metric_value": _safe_float(throughput),
                        "source_file": path.name,
                        "threads": threads,
                    }
                )
            )

        p99_us = bench.get("p99_us")
        if p99_us is not None:
            records.append(
                normalize_benchmark_record(
                    {
                        "benchmark_name": f"{benchmark_name} (p99)",
                        "workload": category,
                        "engine": "ThemisDB",
                        "metric_name": "latency",
                        "metric_unit": "ms",
                        "metric_value": _to_latency_ms(p99_us, "us"),
                        "source_file": path.name,
                        "threads": threads,
                    }
                )
            )
    return records


def _list_payload_records(path: Path, rows: Sequence[Any]) -> List[Dict[str, Any]]:
    records: List[Dict[str, Any]] = []
    default_category = infer_category_from_name(path.stem)
    for item in rows:
        if not isinstance(item, Mapping):
            continue
        normalized = normalize_benchmark_record(
            {
                **item,
                "workload": item.get("workload") or item.get("category") or default_category,
                "source_file": path.name,
            }
        )
        records.append(normalized)
    return records


def load_benchmark_rows(path: str | Path) -> List[Dict[str, Any]]:
    file_path = Path(path)
    if not file_path.exists():
        return []

    suffix = file_path.suffix.lower()
    if suffix == ".csv":
        with file_path.open("r", encoding="utf-8", newline="") as handle:
            reader = csv.DictReader(handle)
            return [normalize_benchmark_record({**row, "source_file": file_path.name}) for row in reader if row]

    if suffix != ".json":
        return []

    payload = json.loads(file_path.read_text(encoding="utf-8", errors="replace"))
    if isinstance(payload, list):
        return _list_payload_records(file_path, payload)
    if isinstance(payload, dict):
        google_records = _google_benchmark_records(file_path, payload)
        if google_records:
            return google_records
        for key in ("records", "results", "data"):
            value = payload.get(key)
            if isinstance(value, list):
                return _list_payload_records(file_path, value)
        return []
    return []


def _metric_suffix(metric: str) -> str:
    mapping = {"latency": "ms", "throughput": "ops/s", "memory": "MB"}
    return mapping.get(metric.lower(), "")


def build_mermaid_flowchart(rows: Sequence[Mapping[str, Any]], metric: str = "latency", category: str = "all") -> str:
    if not rows:
        return "flowchart LR\n    A[No benchmark data available]"

    metric_name = normalize_metric_name(metric)
    grouped: Dict[str, float] = {}
    for row in rows:
        if metric_name != normalize_metric_name(row.get("metric_name") or row.get("metric") or "latency"):
            continue
        engine = str(row.get("engine") or "ThemisDB").strip() or "ThemisDB"
        value = _safe_float(row.get("metric_value"), default=0.0)
        grouped[engine] = value if engine not in grouped else max(grouped[engine], value)

    if not grouped:
        return "flowchart LR\n    A[No benchmark data available]"

    ordered = sorted(grouped.items(), key=lambda item: item[1])
    metric_unit = _metric_suffix(metric_name)
    category_label = str(category or "all").replace("-", " ").title()

    lines = ["flowchart LR", f'    A["{category_label} benchmark"]']
    for index, (engine, value) in enumerate(ordered[:6]):
        node_id = f"n{index}"
        lines.append(f'    {node_id}["{engine}: {value:.2f} {metric_unit}"]')
    for index in range(1, len(ordered[:6])):
        lines.append(f"    n0 --> n{index}")
    if len(ordered) >= 2:
        lines.append(f'    A --> n0["Winner: {ordered[0][0]}"]')
    return "\n".join(lines)


def normalize_benchmark_dataset(rows: Sequence[Mapping[str, Any]], metric: str = "latency", category: str = "all") -> Dict[str, Any]:
    records = [normalize_benchmark_record(row) for row in rows]
    metric_name = normalize_metric_name(metric)

    filtered = [
        row for row in records
        if normalize_metric_name(row.get("metric_name") or "latency") == metric_name
        and (category == "all" or str(row.get("workload") or "all") == category)
    ]
    if not filtered:
        filtered = [row for row in records if normalize_metric_name(row.get("metric_name") or "latency") == metric_name]

    by_engine: Dict[str, List[float]] = {}
    for row in filtered:
        engine = str(row.get("engine") or "ThemisDB").strip() or "ThemisDB"
        by_engine.setdefault(engine, []).append(_safe_float(row.get("metric_value")))

    datasets = []
    for engine, values in sorted(by_engine.items()):
        average = sum(values) / len(values) if values else 0.0
        datasets.append({"label": engine, "data": [round(value, 3) for value in values], "average": round(average, 3)})

    ordered = sorted(datasets, key=lambda item: item["average"])
    return {
        "labels": [str(item.get("label") or "benchmark") for item in ordered],
        "datasets": ordered,
        "metric": metric_name,
        "category": category,
        "mermaid": build_mermaid_flowchart(filtered, metric=metric_name, category=category),
    }


def build_benchmark_catalog(source_dir: str | Path) -> Dict[str, Any]:
    root = Path(source_dir)
    files = sorted(
        path
        for path in root.glob("*.json")
        if path.is_file() and not path.name.lower().startswith("benchmark_catalog")
    )
    records: List[Dict[str, Any]] = []
    files_meta: List[Dict[str, Any]] = []
    errors: List[Dict[str, str]] = []

    for path in files:
        try:
            rows = load_benchmark_rows(path)
            files_meta.append(
                {
                    "file_name": path.name,
                    "parse_status": "ok",
                    "records_count": len(rows),
                    "category": infer_category_from_name(path.stem),
                }
            )
            records.extend(rows)
        except Exception as exc:
            files_meta.append(
                {
                    "file_name": path.name,
                    "parse_status": "invalid",
                    "records_count": 0,
                    "category": infer_category_from_name(path.stem),
                }
            )
            errors.append({"file_name": path.name, "error": str(exc)})

    categories = sorted({str(item.get("workload") or "all") for item in records})
    metrics = sorted({normalize_metric_name(item.get("metric_name") or "latency") for item in records})
    engines = sorted({str(item.get("engine") or "ThemisDB") for item in records})

    return {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "source_dir": str(root),
        "files": files_meta,
        "errors": errors,
        "records": records,
        "stats": {
            "total_files": len(files_meta),
            "parsed_files": sum(1 for item in files_meta if item["parse_status"] == "ok"),
            "failed_files": sum(1 for item in files_meta if item["parse_status"] != "ok"),
            "total_records": len(records),
            "categories": categories,
            "metrics": metrics,
            "engines": engines,
        },
    }


def write_benchmark_catalog(catalog: Mapping[str, Any], output_path: str | Path) -> Path:
    target = Path(output_path)
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(json.dumps(catalog, ensure_ascii=False, indent=2), encoding="utf-8")
    return target


def _build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Build normalized benchmark catalog JSON from benchmark result files")
    parser.add_argument("--source-dir", required=True, help="Directory with benchmark JSON result files")
    parser.add_argument("--output", required=True, help="Target catalog JSON path")
    parser.add_argument("--summary", action="store_true", help="Print compact summary after writing catalog")
    return parser


def main() -> None:
    parser = _build_parser()
    args = parser.parse_args()
    catalog = build_benchmark_catalog(args.source_dir)
    target = write_benchmark_catalog(catalog, args.output)
    if args.summary:
        stats = catalog.get("stats", {})
        print(
            f"catalog={target} files={stats.get('total_files', 0)} parsed={stats.get('parsed_files', 0)} "
            f"failed={stats.get('failed_files', 0)} records={stats.get('total_records', 0)}"
        )


if __name__ == "__main__":
    main()
