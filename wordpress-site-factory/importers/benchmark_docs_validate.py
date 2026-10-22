from __future__ import annotations

import argparse
import importlib.util
import json
import re
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Callable, Dict, Iterable, List, Optional, Set, Tuple

try:
    from benchmark_docs_manifest import build_benchmark_docs_manifest
except ModuleNotFoundError:
    _manifest_path = Path(__file__).resolve().parent / "benchmark_docs_manifest.py"
    _manifest_spec = importlib.util.spec_from_file_location("benchmark_docs_manifest", _manifest_path)
    _manifest_module = importlib.util.module_from_spec(_manifest_spec)
    assert _manifest_spec is not None and _manifest_spec.loader is not None
    _manifest_spec.loader.exec_module(_manifest_module)
    build_benchmark_docs_manifest = _manifest_module.build_benchmark_docs_manifest


BENCHMARK_FILE_PATTERN = re.compile(r"(bench_[a-z0-9_\.\-]+\.json|benchmark_results\.json)", flags=re.IGNORECASE)
NUMERIC_TOKEN_PATTERN = re.compile(
    r"\b(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d+)?|\d+(?:[.,]\d+)?)\s*(ms|ops/s|ops/sec|x|%)\b",
    flags=re.IGNORECASE,
)
HTML_TAG_PATTERN = re.compile(r"<[^>]+>")
WHITESPACE_PATTERN = re.compile(r"\s+")


def _infer_category_from_text(text: str) -> str:
    value = str(text or "").strip().lower()
    mapping = {
        "vector_search": ("vector", "embedding", "hnsw", "simd_distance", "knn"),
        "graph_traversal": ("graph", "pagerank", "traversal", "bfs", "dfs"),
        "transaction": ("tpcc", "transaction", "ycsb", "mvcc", "lock"),
        "compression": ("compression", "zstd", "gorilla", "codec"),
        "advanced": ("aql", "changefeed", "async", "batch", "querymix", "query"),
        "content": ("content", "blob", "branch", "document", "index"),
        "gpu": ("gpu", "cuda", "rocm", "vulkan", "acceleration", "dispatch", "nvlink"),
    }
    for category, needles in mapping.items():
        if any(needle in value for needle in needles):
            return category
    return "all"


def _row_category(record: Dict[str, Any]) -> str:
    workload = str(record.get("workload") or "").strip().lower()
    if workload and workload != "all":
        return workload
    text = " ".join(
        [
            str(record.get("benchmark_name") or ""),
            str(record.get("source_file") or ""),
            str(record.get("notes") or ""),
        ]
    )
    return _infer_category_from_text(text)


def _load_json(path: Path) -> Dict[str, Any]:
    payload = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(payload, dict):
        raise RuntimeError(f"Expected JSON object at {path}")
    return payload


def _parse_number(value: str) -> float:
    text = value.strip()
    if "," in text and "." in text:
        if text.rfind(",") > text.rfind("."):
            text = text.replace(".", "").replace(",", ".")
        else:
            text = text.replace(",", "")
    elif text.count(".") > 1 and "," not in text:
        text = text.replace(".", "")
    elif "," in text and "." not in text:
        if len(text.split(",")[-1]) == 3 and len(text) > 4:
            text = text.replace(",", "")
        else:
            text = text.replace(",", ".")
    return float(text)


def _canonical_numeric_token(token: str) -> Optional[str]:
    match = NUMERIC_TOKEN_PATTERN.search(str(token or ""))
    if not match:
        return None
    numeric = _parse_number(match.group(1))
    unit = match.group(2).lower()
    if unit == "ops/sec":
        unit = "ops/s"
    if unit == "%":
        return f"{numeric:.4f}%"
    return f"{numeric:.4f}{unit}"


def _extract_numeric_tokens(text: str) -> Set[str]:
    tokens: Set[str] = set()
    for match in NUMERIC_TOKEN_PATTERN.finditer(text):
        canonical = _canonical_numeric_token(match.group(0))
        if canonical:
            tokens.add(canonical)
    return tokens


def _extract_file_tokens(text: str) -> Set[str]:
    return {item.lower() for item in BENCHMARK_FILE_PATTERN.findall(text or "")}


def _normalize_html_text(html: str) -> str:
    normalized = HTML_TAG_PATTERN.sub(" ", html or "")
    normalized = normalized.lower()
    return WHITESPACE_PATTERN.sub(" ", normalized).strip()


def _build_metric_lookup(catalog: Dict[str, Any]) -> Dict[Tuple[str, str], List[float]]:
    lookup: Dict[Tuple[str, str], List[float]] = {}
    for record in catalog.get("records", []):
        if not isinstance(record, dict):
            continue
        source_file = str(record.get("source_file") or "").strip()
        metric_unit = str(record.get("metric_unit") or "").strip().lower()
        metric_value = record.get("metric_value")
        if not source_file or metric_unit not in {"ms", "ops/s"}:
            continue
        try:
            numeric = float(metric_value)
        except (TypeError, ValueError):
            continue
        lookup.setdefault((source_file, metric_unit), []).append(numeric)
    return lookup


def _build_category_comparability(catalog: Dict[str, Any], metric: str = "latency") -> Dict[str, Any]:
    metric_name = str(metric or "latency").strip().lower()
    labels_by_category: Dict[str, Dict[str, Set[str]]] = {}
    for row in catalog.get("records", []):
        if not isinstance(row, dict):
            continue
        row_metric = str(row.get("metric_name") or "").strip().lower()
        if row_metric != metric_name:
            continue
        category = _row_category(row)
        engine = str(row.get("engine") or "ThemisDB").strip() or "ThemisDB"
        benchmark_name = str(row.get("benchmark_name") or "").strip() or "benchmark"
        labels_by_category.setdefault(category, {})
        labels_by_category[category].setdefault(benchmark_name, set())
        labels_by_category[category][benchmark_name].add(engine)

    categories: Dict[str, Any] = {}
    for category, tasks in labels_by_category.items():
        total_tasks = len(tasks)
        comparable_tasks = sum(1 for engines in tasks.values() if len(engines) >= 2)
        score = round((comparable_tasks / total_tasks) * 100.0, 2) if total_tasks else 0.0
        categories[category] = {
            "tasks_total": total_tasks,
            "tasks_comparable": comparable_tasks,
            "tasks_single_engine": max(0, total_tasks - comparable_tasks),
            "comparability_score": score,
        }

    ordered = sorted(
        categories.items(),
        key=lambda item: (item[1]["comparability_score"], item[1]["tasks_total"], item[0]),
    )
    worst_categories = [{"category": key, **value} for key, value in ordered[:3]]
    return {"metric": metric_name, "categories": categories, "worst_categories": worst_categories}


def _to_float(value: Any) -> Optional[float]:
    try:
        return float(value)
    except (TypeError, ValueError):
        return None


def _value_supported(value: float, unit: str, metric_values: Iterable[float]) -> bool:
    for candidate in metric_values:
        if unit == "ms":
            if abs(candidate - value) <= max(0.25, abs(value) * 0.2):
                return True
        elif unit == "ops/s":
            if abs(candidate - value) <= max(200.0, abs(value) * 0.25):
                return True
    return False


def _load_docs_import_module():
    try:
        import docs_import as module
        return module
    except ModuleNotFoundError:
        module_path = Path(__file__).resolve().parent / "docs_import.py"
        spec = importlib.util.spec_from_file_location("docs_import", module_path)
        module = importlib.util.module_from_spec(spec)
        assert spec is not None and spec.loader is not None
        spec.loader.exec_module(module)
        return module


def _build_evolution_snapshot(current: Dict[str, Any], previous: Optional[Dict[str, Any]]) -> Dict[str, Any]:
    tracked_metrics = [
        "docs_with_data_backing",
        "docs_missing_related_files",
        "docs_with_missing_expectation_refs",
        "docs_with_wp_numeric_mismatch",
        "docs_with_unsupported_data_claims",
        "numeric_token_match_rate",
        "file_ref_match_rate",
        "comparability_score_global",
    ]
    if not previous:
        return {
            "status": "no-baseline",
            "tracked_metrics": tracked_metrics,
            "deltas": {},
        }

    deltas: Dict[str, Any] = {}
    for key in tracked_metrics:
        current_value = current.get(key)
        previous_value = previous.get(key)
        current_float = _to_float(current_value)
        previous_float = _to_float(previous_value)
        if current_float is None or previous_float is None:
            delta = None
        else:
            delta = round(current_float - previous_float, 4)
        deltas[key] = {
            "current": current_value,
            "previous": previous_value,
            "delta": delta,
        }

    quality_score_current = (
        (current.get("numeric_token_match_rate") or 0.0)
        - float(current.get("docs_with_missing_expectation_refs") or 0) * 2.0
        - float(current.get("docs_with_unsupported_data_claims") or 0) * 1.5
        - float(current.get("docs_missing_related_files") or 0) * 1.0
        + float(current.get("comparability_score_global") or 0.0) * 0.1
    )
    quality_score_previous = (
        (previous.get("numeric_token_match_rate") or 0.0)
        - float(previous.get("docs_with_missing_expectation_refs") or 0) * 2.0
        - float(previous.get("docs_with_unsupported_data_claims") or 0) * 1.5
        - float(previous.get("docs_missing_related_files") or 0) * 1.0
        + float(previous.get("comparability_score_global") or 0.0) * 0.1
    )
    quality_delta = round(float(quality_score_current) - float(quality_score_previous), 4)

    trend = "stable"
    if quality_delta > 0.01:
        trend = "improving"
    elif quality_delta < -0.01:
        trend = "regressing"

    return {
        "status": "compared",
        "tracked_metrics": tracked_metrics,
        "deltas": deltas,
        "quality_score_current": round(float(quality_score_current), 4),
        "quality_score_previous": round(float(quality_score_previous), 4),
        "quality_score_delta": quality_delta,
        "trend": trend,
    }


def _resolve_wp_content_loader(
    base_url: str,
    user: str,
    password: str,
) -> Callable[[Dict[str, Any]], Optional[str]]:
    docs_import = _load_docs_import_module()
    resolved = docs_import.resolve_runtime_credentials(base_url, user, password)
    resolved_user = str(resolved.get("user") or "")
    resolved_password = str(resolved.get("password") or "")

    def _fetch_wp_content(doc: Dict[str, Any]) -> Optional[str]:
        post = docs_import.existing_wp_doc(
            base_url,
            resolved_user,
            resolved_password,
            str(doc.get("source_path") or ""),
            str(doc.get("slug") or ""),
            str(doc.get("title") or ""),
        )
        if not post or not isinstance(post, dict):
            return None
        content = post.get("content")
        if isinstance(content, dict):
            rendered = content.get("rendered")
            return str(rendered or "")
        return None

    return _fetch_wp_content


def validate_benchmark_docs(
    docs_root: Path,
    catalog_path: Path,
    base_url: str,
    user: str,
    password: str,
    max_details: int = 100,
    repo_root: Optional[Path] = None,
    previous_report: Optional[Path] = None,
    wp_content_loader: Optional[Callable[[Dict[str, Any]], Optional[str]]] = None,
) -> Dict[str, Any]:
    manifest = build_benchmark_docs_manifest(docs_root, repo_root=repo_root)
    catalog = _load_json(catalog_path)
    wp_loader = wp_content_loader or _resolve_wp_content_loader(base_url, user, password)

    files_meta = {
        str(entry.get("file_name") or ""): entry
        for entry in catalog.get("files", [])
        if isinstance(entry, dict)
    }
    valid_files = {
        file_name
        for file_name, meta in files_meta.items()
        if str(meta.get("parse_status") or "") == "ok" and int(meta.get("records_count") or 0) > 0
    }
    metric_lookup = _build_metric_lookup(catalog)

    report: Dict[str, Any] = {
        "docs_total": int(manifest.get("document_count") or 0),
        "docs_with_related_files": 0,
        "docs_missing_related_files": 0,
        "docs_only_invalid_related_files": 0,
        "docs_with_data_backing": 0,
        "wp_docs_found": 0,
        "wp_docs_missing": 0,
        "numeric_tokens_total": 0,
        "numeric_tokens_matched_in_wp": 0,
        "file_refs_total": 0,
        "file_refs_matched_in_wp": 0,
        "docs_with_wp_numeric_mismatch": 0,
        "docs_with_wp_file_ref_mismatch": 0,
        "docs_with_data_numeric_claims": 0,
        "docs_with_supported_data_claims": 0,
        "docs_with_fully_supported_data_claims": 0,
        "docs_with_unsupported_data_claims": 0,
        "docs_without_supported_data_claims": 0,
        "docs_with_expectation_refs": 0,
        "docs_with_missing_expectation_refs": 0,
        "comparability_score_global": 0.0,
        "category_comparability": {},
        "details": [],
    }

    for doc in manifest.get("documents", []):
        if not isinstance(doc, dict):
            continue
        source_path = Path(str(doc.get("source_path") or ""))
        source_content = source_path.read_text(encoding="utf-8", errors="replace")
        related_files = list(doc.get("metadata", {}).get("related_benchmark_files") or [])
        expectation_refs = list(doc.get("metadata", {}).get("related_expectation_docs") or [])
        missing_expectation_refs = list(doc.get("metadata", {}).get("missing_expectation_refs") or [])
        existing_related = [item for item in related_files if item in files_meta]
        valid_related = [item for item in related_files if item in valid_files]

        if related_files:
            report["docs_with_related_files"] += 1
            if not existing_related:
                report["docs_missing_related_files"] += 1
            if existing_related and not valid_related:
                report["docs_only_invalid_related_files"] += 1
            if valid_related:
                report["docs_with_data_backing"] += 1

        if expectation_refs:
            report["docs_with_expectation_refs"] += 1
            if missing_expectation_refs:
                report["docs_with_missing_expectation_refs"] += 1

        source_numeric_tokens = _extract_numeric_tokens(source_content)
        source_file_tokens = _extract_file_tokens(source_content)

        wp_content = wp_loader(doc)
        wp_numeric_tokens: Set[str] = set()
        wp_file_tokens: Set[str] = set()

        detail: Dict[str, Any] = {
            "slug": doc.get("slug"),
            "title": doc.get("title"),
            "related_files": related_files,
            "related_files_existing": existing_related,
            "related_files_valid": valid_related,
            "related_expectation_docs": expectation_refs,
            "missing_expectation_refs": missing_expectation_refs,
            "wp_found": wp_content is not None,
            "missing_wp_numeric_tokens": [],
            "missing_wp_file_refs": [],
            "unsupported_data_claims": [],
        }

        if wp_content is None:
            report["wp_docs_missing"] += 1
            detail["missing_wp_numeric_tokens"] = sorted(source_numeric_tokens)
            detail["missing_wp_file_refs"] = sorted(source_file_tokens)
        else:
            report["wp_docs_found"] += 1
            normalized_wp = _normalize_html_text(wp_content)
            wp_numeric_tokens = _extract_numeric_tokens(normalized_wp)
            wp_file_tokens = _extract_file_tokens(normalized_wp)

            missing_numeric = sorted(source_numeric_tokens - wp_numeric_tokens)
            missing_file_refs = sorted(source_file_tokens - wp_file_tokens)
            detail["missing_wp_numeric_tokens"] = missing_numeric
            detail["missing_wp_file_refs"] = missing_file_refs

            if missing_numeric:
                report["docs_with_wp_numeric_mismatch"] += 1
            if missing_file_refs:
                report["docs_with_wp_file_ref_mismatch"] += 1

        report["numeric_tokens_total"] += len(source_numeric_tokens)
        report["numeric_tokens_matched_in_wp"] += len(source_numeric_tokens & wp_numeric_tokens)
        report["file_refs_total"] += len(source_file_tokens)
        report["file_refs_matched_in_wp"] += len(source_file_tokens & wp_file_tokens)

        if valid_related and source_numeric_tokens:
            report["docs_with_data_numeric_claims"] += 1
            supported_claims = 0
            unsupported_claims: List[str] = []
            checked_claims = 0
            for token in sorted(source_numeric_tokens):
                metric_match = re.match(r"^([0-9.]+)(ms|ops/s)$", token)
                if not metric_match:
                    continue
                checked_claims += 1
                value = float(metric_match.group(1))
                unit = metric_match.group(2)
                values: List[float] = []
                for related_file in valid_related:
                    values.extend(metric_lookup.get((related_file, unit), []))
                if _value_supported(value, unit, values):
                    supported_claims += 1
                else:
                    unsupported_claims.append(token)
            if supported_claims > 0:
                report["docs_with_supported_data_claims"] += 1
            else:
                report["docs_without_supported_data_claims"] += 1
            if checked_claims > 0 and not unsupported_claims:
                report["docs_with_fully_supported_data_claims"] += 1
            if unsupported_claims:
                report["docs_with_unsupported_data_claims"] += 1
            detail["unsupported_data_claims"] = unsupported_claims

        if (
            detail["missing_wp_numeric_tokens"]
            or detail["missing_wp_file_refs"]
            or detail["unsupported_data_claims"]
            or (related_files and not valid_related)
            or missing_expectation_refs
            or wp_content is None
        ) and len(report["details"]) < max_details:
            report["details"].append(detail)

    report["numeric_token_match_rate"] = (
        round((report["numeric_tokens_matched_in_wp"] / report["numeric_tokens_total"]) * 100.0, 2)
        if report["numeric_tokens_total"]
        else None
    )
    report["file_ref_match_rate"] = (
        round((report["file_refs_matched_in_wp"] / report["file_refs_total"]) * 100.0, 2)
        if report["file_refs_total"]
        else None
    )
    comparability = _build_category_comparability(catalog, metric="latency")
    report["category_comparability"] = comparability
    category_scores = [
        float(item.get("comparability_score") or 0.0)
        for item in comparability.get("categories", {}).values()
        if isinstance(item, dict)
    ]
    report["comparability_score_global"] = (
        round(sum(category_scores) / len(category_scores), 2) if category_scores else 0.0
    )
    previous_payload: Optional[Dict[str, Any]] = None
    if previous_report and Path(previous_report).exists():
        try:
            previous_payload = _load_json(Path(previous_report))
        except Exception:
            previous_payload = None
    report["ctcb_evolution"] = _build_evolution_snapshot(report, previous_payload)
    report["generated_at"] = datetime.now(timezone.utc).isoformat()
    return report


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Validate benchmark docs against benchmark data and WordPress-rendered content."
    )
    parser.add_argument("--docs-root", required=True, help="Directory containing benchmark markdown docs")
    parser.add_argument("--catalog", required=True, help="Path to benchmark_catalog.json")
    parser.add_argument("--repo-root", default="", help="Repository root for PERFORMANCE_EXPECTATION*.md discovery")
    parser.add_argument("--output", required=True, help="Output JSON validation report")
    parser.add_argument("--previous-report", default="", help="Optional previous validation report for CT/CB trend deltas")
    parser.add_argument("--base-url", default="http://localhost/wordpress", help="WordPress base URL")
    parser.add_argument("--user", default="", help="WordPress user (optional when auto-resolving local profile)")
    parser.add_argument("--password", default="", help="WordPress app password")
    parser.add_argument("--max-details", type=int, default=100, help="Maximum mismatch details stored in report")
    parser.add_argument("--summary", action="store_true", help="Print compact summary")
    args = parser.parse_args()

    report = validate_benchmark_docs(
        docs_root=Path(args.docs_root),
        catalog_path=Path(args.catalog),
        base_url=args.base_url,
        user=args.user,
        password=args.password,
        max_details=max(0, int(args.max_details)),
        repo_root=Path(args.repo_root) if args.repo_root else None,
        previous_report=Path(args.previous_report) if args.previous_report else None,
    )

    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

    if args.summary:
        print(
            " ".join(
                [
                    f"report={output_path}",
                    f"docs_total={report['docs_total']}",
                    f"wp_docs_found={report['wp_docs_found']}",
                    f"docs_with_data_backing={report['docs_with_data_backing']}",
                    f"docs_with_expectation_refs={report['docs_with_expectation_refs']}",
                    f"docs_with_missing_expectation_refs={report['docs_with_missing_expectation_refs']}",
                    f"comparability_score_global={report['comparability_score_global']}",
                    f"numeric_match_rate={report['numeric_token_match_rate']}",
                    f"file_ref_match_rate={report['file_ref_match_rate']}",
                    f"data_claims_any_supported={report['docs_with_supported_data_claims']}/{report['docs_with_data_numeric_claims']}",
                    f"data_claims_fully_supported={report['docs_with_fully_supported_data_claims']}/{report['docs_with_data_numeric_claims']}",
                    f"data_claims_with_unsupported={report['docs_with_unsupported_data_claims']}",
                    f"ctcb_trend={report.get('ctcb_evolution', {}).get('trend', 'n/a')}",
                ]
            )
        )


if __name__ == "__main__":
    main()
