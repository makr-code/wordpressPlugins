from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any, Dict

from bootstrap import ensure_ai_coauthors, ensure_site_ready, load_json
from pages import generate_pages
from taxonomy import apply_taxonomy
from validation import smoke_test

ROOT = Path(__file__).resolve().parent
CONFIG_PATH = ROOT / "config" / "site.json"


def load_config(profile: str | None = None) -> Dict[str, Any]:
    config = load_json(CONFIG_PATH)
    if profile:
        config["profile"] = profile
        # keep the same base config; profile-specific overrides can be added here later
    return config


def main() -> None:
    parser = argparse.ArgumentParser(description="Build the ThemisDB public WordPress site from a central factory")
    parser.add_argument("--dry-run", action="store_true", help="Preview the setup without making API calls")
    parser.add_argument("--profile", default="local-test", help="Configuration profile to use")
    parser.add_argument("--scan-sources", action="store_true", help="Scan the source-links bridge and print the import manifest")
    parser.add_argument("--scan-docs", action="store_true", help="Scan the documentation cache and print the document-level manifest with hashes")
    args = parser.parse_args()

    config = load_config(args.profile)
    print(f"[factory] Starting ThemisDB WordPress site factory with profile '{args.profile}'")

    if args.scan_sources:
        from importers.source_manifest import build_manifest, SOURCE_ROOT
        manifest = build_manifest(SOURCE_ROOT, [
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
        ])
        print(json.dumps(manifest, ensure_ascii=False, indent=2))
        return

    if args.scan_docs:
        from importers.docs_manifest import build_manifest, SOURCE_ROOT
        manifest = build_manifest(SOURCE_ROOT)
        print(json.dumps(manifest, ensure_ascii=False, indent=2))
        return

    ensure_site_ready(config, dry_run=args.dry_run)
    ensure_ai_coauthors(config, dry_run=args.dry_run)
    apply_taxonomy(config, dry_run=args.dry_run)
    generate_pages(config, dry_run=args.dry_run)
    smoke_test(config, dry_run=args.dry_run)

    print("[factory] Done")


if __name__ == "__main__":
    main()
