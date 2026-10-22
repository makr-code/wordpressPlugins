from __future__ import annotations

import argparse
import importlib.util
import json
from pathlib import Path
from typing import Any, Dict, List

try:
    from docs_import import DEFAULT_BASE_URL, DEFAULT_PASSWORD, DEFAULT_USER, existing_wp_doc
except ModuleNotFoundError:
    _docs_import_path = Path(__file__).resolve().parent / "docs_import.py"
    _spec = importlib.util.spec_from_file_location("docs_import", _docs_import_path)
    _module = importlib.util.module_from_spec(_spec)
    assert _spec is not None and _spec.loader is not None
    _spec.loader.exec_module(_module)
    DEFAULT_BASE_URL = _module.DEFAULT_BASE_URL
    DEFAULT_USER = _module.DEFAULT_USER
    DEFAULT_PASSWORD = _module.DEFAULT_PASSWORD
    existing_wp_doc = _module.existing_wp_doc


def _load_manifest(path: Path) -> Dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def _write_json(path: Path, payload: Dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")


def build_existing_only_manifest(
    manifest_path: Path,
    output_path: Path,
    base_url: str,
    user: str,
    password: str,
) -> Dict[str, Any]:
    manifest = _load_manifest(manifest_path)
    documents = manifest.get("documents", [])
    existing_docs: List[Dict[str, Any]] = []
    missing_docs: List[Dict[str, str]] = []

    for doc in documents:
        if not isinstance(doc, dict):
            continue
        existing = existing_wp_doc(
            base_url,
            user,
            password,
            str(doc.get("source_path") or ""),
            str(doc.get("slug") or ""),
            str(doc.get("title") or ""),
        )
        if existing:
            existing_docs.append(doc)
        else:
            missing_docs.append(
                {
                    "title": str(doc.get("title") or ""),
                    "slug": str(doc.get("slug") or ""),
                }
            )

    result = {
        "generated_from": str(manifest_path),
        "total_documents": len([d for d in documents if isinstance(d, dict)]),
        "existing_documents": len(existing_docs),
        "missing_documents": len(missing_docs),
        "documents": existing_docs,
        "missing": missing_docs,
    }
    _write_json(output_path, result)
    return result


def main() -> None:
    parser = argparse.ArgumentParser(description="Filter benchmark docs manifest to WordPress-existing docs only.")
    parser.add_argument("--manifest", required=True, help="Input benchmark docs manifest JSON")
    parser.add_argument("--output", required=True, help="Output manifest with only existing docs")
    parser.add_argument("--base-url", default=DEFAULT_BASE_URL, help="WordPress base URL")
    parser.add_argument("--user", default=DEFAULT_USER, help="WordPress user")
    parser.add_argument("--password", default=DEFAULT_PASSWORD, help="WordPress app password")
    args = parser.parse_args()

    result = build_existing_only_manifest(
        manifest_path=Path(args.manifest),
        output_path=Path(args.output),
        base_url=args.base_url,
        user=args.user,
        password=args.password,
    )
    print(
        f"manifest={args.output} total={result['total_documents']} "
        f"existing={result['existing_documents']} missing={result['missing_documents']}"
    )


if __name__ == "__main__":
    main()
