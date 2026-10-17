from __future__ import annotations

import argparse
import json
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, Iterable, List

ROOT = Path(__file__).resolve().parents[1]
SOURCE_ROOT = ROOT.parent / "source-links"
DEFAULT_TARGETS = [
    "docs",
    "benchmarks",
    "downloads",
    "releases",
    "examples",
    "compendium",
    "reports",
    "api",
    "assets",
    "data",
]

TAG_HINTS = {
    "docs": ["documentation", "guide"],
    "benchmarks": ["benchmark", "performance"],
    "downloads": ["download", "package"],
    "releases": ["release", "changelog"],
    "examples": ["example", "demo"],
    "compendium": ["compendium", "reference"],
    "reports": ["report", "analysis"],
    "api": ["api", "reference"],
    "assets": ["asset", "media"],
    "data": ["data", "dataset"],
}


def classify_file(path: Path, target: str) -> Dict[str, str]:
    suffix = path.suffix.lower().lstrip(".")
    name = path.name.lower()
    if suffix in {"md", "mdx", "rst", "txt"}:
        kind = "text"
    elif suffix in {"html", "htm"}:
        kind = "html"
    elif suffix in {"json", "yaml", "yml"}:
        kind = "data"
    elif suffix in {"png", "jpg", "jpeg", "svg", "webp", "gif"}:
        kind = "image"
    elif suffix in {"zip", "tar", "gz", "bz2", "xz"}:
        kind = "archive"
    else:
        kind = "binary"

    hints = TAG_HINTS.get(target, [target])
    title = path.stem.replace("_", " ").replace("-", " ")
    return {
        "kind": kind,
        "extension": suffix,
        "title": title.title(),
        "tags": hints,
        "name": name,
    }


def build_manifest(source_root: Path, targets: Iterable[str]) -> Dict[str, Any]:
    target_list = list(targets)
    manifest: Dict[str, Any] = {
        "source_root": str(source_root),
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "targets": [],
    }

    for target in target_list:
        directory = source_root / target
        if not directory.exists():
            manifest["targets"].append({
                "name": target,
                "exists": False,
                "files": [],
                "count": 0,
                "extensions": {},
            })
            continue

        files: List[Dict[str, Any]] = []
        extensions: Dict[str, int] = {}
        for path in sorted(directory.rglob("*")):
            if path.is_dir() or path.name.startswith("."):
                continue

            rel = path.relative_to(directory)
            file_meta = classify_file(path, target)
            record = {
                "path": str(rel).replace("\\", "/"),
                "title": file_meta["title"],
                "kind": file_meta["kind"],
                "extension": file_meta["extension"],
                "tags": file_meta["tags"],
                "size": path.stat().st_size,
            }
            files.append(record)
            extensions[file_meta["extension"]] = extensions.get(file_meta["extension"], 0) + 1

        manifest["targets"].append({
            "name": target,
            "exists": True,
            "files": files,
            "count": len(files),
            "extensions": dict(sorted(extensions.items())),
        })

    manifest["total_files"] = sum(item["count"] for item in manifest["targets"])
    return manifest


def main() -> None:
    parser = argparse.ArgumentParser(description="Scan the linked source directories and create a manifest for the WordPress import pipeline")
    parser.add_argument("--sources-root", default=str(SOURCE_ROOT), help="Directory containing the source-links bridge folders")
    parser.add_argument("--targets", nargs="*", default=DEFAULT_TARGETS, help="Source directories to scan under source-links")
    parser.add_argument("--json", action="store_true", help="Print the manifest as JSON")
    parser.add_argument("--write", action="store_true", help="Persist the generated manifest to wordpress-site-factory/output/source_manifest.json")
    args = parser.parse_args()

    source_root = Path(args.sources_root).resolve()
    manifest = build_manifest(source_root, args.targets)

    if args.write:
        output_dir = ROOT / "output"
        output_dir.mkdir(exist_ok=True)
        output_path = output_dir / "source_manifest.json"
        output_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
        print(f"[manifest] Wrote {output_path}")

    if args.json or args.write:
        if args.json:
            print(json.dumps(manifest, ensure_ascii=False, indent=2))
        return

    for target in manifest["targets"]:
        status = "OK" if target["exists"] else "MISSING"
        print(f"[{status}] {target['name']}: {target['count']} files")
        if target["exists"] and target["count"]:
            for item in target["files"][:5]:
                print(f"  - {item['path']} ({item['kind']}, {item['extension']})")
            if target["count"] > 5:
                print("  - ...")


if __name__ == "__main__":
    main()
