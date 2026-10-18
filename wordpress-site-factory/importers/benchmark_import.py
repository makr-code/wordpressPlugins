from __future__ import annotations

import csv
import json
from pathlib import Path
from typing import Any, Dict, Iterable, List, Mapping, Sequence, Tuple


def normalize_metric_name(raw: Any) -> str:
    value = str(raw or "").strip().lower()
    aliases = {
        "latency": "latency",
        "latency_ms": "latency",
        "throughput": "throughput",
        "throughput_ops": "throughput",
        "memory": "memory",
        "memory_mb": "memory",
        "ops_sec": "throughput",
    }
    return aliases.get(value, value or "latency")


def normalize_benchmark_record(raw: Mapping[str, Any]) -> Dict[str, Any]:
    record = {}
    for key, value in raw.items():
        if value is None:
            continue
        record[str(key)] = value

    engine = str(record.get("engine") or record.get("database") or record.get("db") or "ThemisDB").strip() or "ThemisDB"
    benchmark_name = str(record.get("benchmark_name") or record.get("benchmark") or record.get("name") or "benchmark").strip() or "benchmark"
    workload = str(record.get("workload") or record.get("category") or "all").strip() or "all"
    metric_name = normalize_metric_name(record.get("metric_name") or record.get("metric") or "latency")
    metric_unit = str(record.get("metric_unit") or record.get("unit") or {
        "latency": "ms",
        "throughput": "ops/s",
        "memory": "MB",
    }.get(metric_name, "")).strip()
    metric_value = record.get("metric_value")
    if metric_value is None:
        metric_value = record.get("value")
    try:
        metric_value = float(metric_value)
    except (TypeError, ValueError):
        metric_value = 0.0

    dataset_size = str(record.get("dataset_size") or record.get("dataset") or record.get("scale") or "n/a").strip() or "n/a"
    environment = str(record.get("environment") or record.get("target") or "").strip()
    notes = str(record.get("notes") or record.get("description") or "").strip()
    return {
        "benchmark_name": benchmark_name,
        "workload": workload,
        "dataset_size": dataset_size,
        "engine": engine,
        "environment": environment,
        "metric_name": metric_name,
        "metric_unit": metric_unit,
        "metric_value": metric_value,
        "notes": notes,
    }


def load_benchmark_rows(path: str | Path) -> List[Dict[str, Any]]:
    file_path = Path(path)
    if not file_path.exists():
        return []

    suffix = file_path.suffix.lower()
    if suffix == ".csv":
        with file_path.open("r", encoding="utf-8", newline="") as handle:
            reader = csv.DictReader(handle)
            return [normalize_benchmark_record(row) for row in reader if row]

    if suffix in {".json"}:
        payload = json.loads(file_path.read_text(encoding="utf-8"))
        if isinstance(payload, list):
            return [normalize_benchmark_record(item) for item in payload if isinstance(item, Mapping)]
        if isinstance(payload, dict):
            for key in ("records", "benchmarks", "results", "data"):
                value = payload.get(key)
                if isinstance(value, list):
                    return [normalize_benchmark_record(item) for item in value if isinstance(item, Mapping)]
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
        engine = str(row.get("engine") or "ThemisDB").strip() or "ThemisDB"
        value = float(row.get("metric_value") or 0.0)
        if metric_name != normalize_metric_name(row.get("metric_name") or row.get("metric") or "latency"):
            continue
        grouped[engine] = value if engine not in grouped else max(grouped[engine], value)

    if not grouped:
        grouped = {
            str(row.get("engine") or "ThemisDB"): float(row.get("metric_value") or 0.0)
            for row in rows
        }

    ordered = sorted(grouped.items(), key=lambda item: item[1])
    metric_unit = _metric_suffix(metric_name)
    category_label = str(category or "all").replace("-", " ").title()

    lines = [
        "flowchart LR",
        f'    A["{category_label} benchmark"]',
    ]

    for index, (engine, value) in enumerate(ordered[:6]):
        node_id = f"n{index}"
        lines.append(f'    {node_id}["{engine}: {value:.2f} {metric_unit}"]')
    for index in range(1, len(ordered[:6])):
        lines.append(f"    n0 --> n{index}")

    if len(ordered) >= 2:
        winner = ordered[0][0]
        lines.append(f'    A --> n0["Winner: {winner}"]')

    return "\n".join(lines)


def normalize_benchmark_dataset(rows: Sequence[Mapping[str, Any]], metric: str = "latency", category: str = "all") -> Dict[str, Any]:
    records = [normalize_benchmark_record(row) for row in rows]
    metric_name = normalize_metric_name(metric)

    by_engine: Dict[str, List[float]] = {}
    for row in records:
        engine = str(row.get("engine") or "ThemisDB")
        if normalize_metric_name(row.get("metric_name") or "latency") != metric_name:
            continue
        by_engine.setdefault(engine, []).append(float(row.get("metric_value") or 0.0))

    datasets = []
    for engine, values in sorted(by_engine.items()):
        avg = sum(values) / len(values) if values else 0.0
        datasets.append({
            "label": engine,
            "data": [round(value, 3) for value in values],
            "average": round(avg, 3),
        })

    ordered = sorted(datasets, key=lambda item: item["average"])
    chart_data = {
        "labels": [str(item.get("label") or "benchmark") for item in ordered],
        "datasets": [
            {
                "label": item["label"],
                "data": item["data"],
                "average": item["average"],
            }
            for item in ordered
        ],
        "metric": metric_name,
        "category": category,
        "mermaid": build_mermaid_flowchart(records, metric=metric_name, category=category),
    }
    return chart_data
