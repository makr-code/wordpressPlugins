from __future__ import annotations

import argparse
import hashlib
import json
import re
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, List, Optional


BENCHMARK_FILE_PATTERN = re.compile(r"(bench_[a-z0-9_\.\-]+\.json|benchmark_results\.json)", flags=re.IGNORECASE)
EXPECTATION_REF_PATTERN = re.compile(r"(?:^|[(/`'\"\s])(PERFORMANCE_EXPECTATION[S]?\.md)(?:$|[)#`'\"\s])", flags=re.IGNORECASE)
HISTORICAL_MARKERS = (
    "Historischer Statusreport",
    "Historical status",
    "Historical snapshot",
    "Status: Historical snapshot",
    "Canonicality: Non-canonical",
)


def _extract_title(content: str, fallback: str) -> str:
    for line in content.splitlines():
        stripped = line.strip()
        if stripped.startswith("# "):
            return stripped[2:].strip()
    return fallback


def _historical_flag(content: str) -> bool:
    lowered = content.lower()
    return any(marker.lower() in lowered for marker in HISTORICAL_MARKERS)


def _related_benchmark_files(content: str) -> List[str]:
    matches = BENCHMARK_FILE_PATTERN.findall(content)
    unique = []
    for entry in matches:
        value = str(entry).strip()
        if value and value not in unique:
            unique.append(value)
    return unique


def _collect_expectation_docs(repo_root: Optional[Path]) -> Dict[str, List[str]]:
    if repo_root is None:
        return {}
    root = Path(repo_root)
    if not root.exists():
        return {}
    matches: Dict[str, List[str]] = {}
    for pattern in ("**/PERFORMANCE_EXPECTATION.md", "**/PERFORMANCE_EXPECTATIONS.md"):
        for path in root.glob(pattern):
            if not path.is_file():
                continue
            key = path.name.lower()
            matches.setdefault(key, [])
            value = str(path)
            if value not in matches[key]:
                matches[key].append(value)
    return matches


def _related_expectation_docs(content: str) -> List[str]:
    matches = EXPECTATION_REF_PATTERN.findall(content or "")
    unique: List[str] = []
    for entry in matches:
        value = str(entry).strip()
        if value and value not in unique:
            unique.append(value)
    return unique


def build_benchmark_docs_manifest(docs_root: str | Path, repo_root: Optional[str | Path] = None) -> Dict[str, Any]:
    root = Path(docs_root)
    files = sorted(path for path in root.glob("*.md") if path.is_file())
    resolved_repo_root: Optional[Path] = None
    if repo_root is not None:
        resolved_repo_root = Path(repo_root)
    else:
        resolved_repo_root = root.parents[2] if len(root.parents) >= 3 else None
    expectation_docs = _collect_expectation_docs(resolved_repo_root)
    documents: List[Dict[str, Any]] = []
    historical_count = 0
    expectation_reference_count = 0

    for path in files:
        content = path.read_text(encoding="utf-8", errors="replace")
        title = _extract_title(content, path.stem)
        is_historical = _historical_flag(content)
        if is_historical:
            historical_count += 1
        related_files = _related_benchmark_files(content)
        related_expectations = _related_expectation_docs(content)
        if related_expectations:
            expectation_reference_count += 1

        expectation_resolution: Dict[str, List[str]] = {}
        missing_expectation_refs: List[str] = []
        for ref in related_expectations:
            resolved = expectation_docs.get(ref.lower(), [])
            if resolved:
                expectation_resolution[ref] = resolved
            else:
                missing_expectation_refs.append(ref)

        documents.append(
            {
                "title": title,
                "slug": re.sub(r"[^a-z0-9]+", "-", title.lower()).strip("-") or path.stem.lower(),
                "source_path": str(path),
                "relative_path": str(path),
                "size": path.stat().st_size,
                "hash": hashlib.sha256(content.encode("utf-8")).hexdigest(),
                "last_modified": datetime.fromtimestamp(path.stat().st_mtime, tz=timezone.utc).isoformat(),
                "kind": "benchmark_doc",
                "tags": ["benchmark", "report"],
                "metadata": {
                    "source_origin": "source-links/benchmarks/docs",
                    "is_historical": is_historical,
                    "related_benchmark_files": related_files,
                    "related_expectation_docs": related_expectations,
                    "resolved_expectation_docs": expectation_resolution,
                    "missing_expectation_refs": missing_expectation_refs,
                },
            }
        )

    return {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "source": str(root),
        "document_count": len(documents),
        "historical_count": historical_count,
        "expectation_reference_count": expectation_reference_count,
        "expectation_docs_discovered": sum(len(value) for value in expectation_docs.values()),
        "documents": documents,
    }


def write_manifest(manifest: Dict[str, Any], output_path: str | Path) -> Path:
    target = Path(output_path)
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
    return target


def main() -> None:
    parser = argparse.ArgumentParser(description="Build benchmark docs manifest from source-links/benchmarks/docs")
    parser.add_argument("--docs-root", required=True, help="Directory containing benchmark markdown reports")
    parser.add_argument("--repo-root", default="", help="Repository root for discovering PERFORMANCE_EXPECTATION*.md files")
    parser.add_argument("--output", required=True, help="Target JSON path for manifest")
    parser.add_argument("--summary", action="store_true", help="Print compact summary")
    args = parser.parse_args()

    manifest = build_benchmark_docs_manifest(args.docs_root, repo_root=args.repo_root or None)
    target = write_manifest(manifest, args.output)
    if args.summary:
        print(
            " ".join(
                [
                    f"manifest={target}",
                    f"docs={manifest['document_count']}",
                    f"historical={manifest['historical_count']}",
                    f"expectation_refs={manifest['expectation_reference_count']}",
                    f"expectation_docs_discovered={manifest['expectation_docs_discovered']}",
                ]
            )
        )


if __name__ == "__main__":
    main()
