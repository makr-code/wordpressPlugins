from __future__ import annotations

import json
from typing import Any, Dict, List

from bootstrap import load_json, rest_request


def ensure_term_exists(base_url: str, user: str, password: str, term_name: str, taxonomy: str = "post_tag") -> Dict[str, Any]:
    payload = {"search": term_name, "per_page": 100}
    terms = rest_request(base_url, f"wp-json/wp/v2/{taxonomy}", user, password, payload=payload)
    if isinstance(terms, list):
        for term in terms:
            if isinstance(term, dict) and term.get("slug") == term_name:
                return term

    created = rest_request(
        base_url,
        f"wp-json/wp/v2/{taxonomy}",
        user,
        password,
        method="POST",
        payload={"name": term_name, "slug": term_name},
    )
    return created if isinstance(created, dict) else {"slug": term_name, "name": term_name}


def apply_taxonomy(config: Dict[str, Any], dry_run: bool = False) -> Dict[str, Any]:
    wordpress_cfg = config["wordpress"]
    base_url = wordpress_cfg["base_url"]
    user = wordpress_cfg["user"]
    password = wordpress_cfg["app_password"]

    taxonomy_cfg = load_json(__import__("pathlib").Path(__file__).resolve().parent / "config" / "taxonomy.json")
    selection_tags = taxonomy_cfg.get("selection_tags", [])
    restricted_tags = taxonomy_cfg.get("restricted_tags", [])
    descriptive_tags = taxonomy_cfg.get("descriptive_tags", [])

    print("[taxonomy] Applying reserved tag taxonomy")
    if dry_run:
        print(f"[taxonomy] Dry run: would create {len(selection_tags)} selection tags, {len(restricted_tags)} restricted tags, and {len(descriptive_tags)} descriptive tags")
        return {"selection_tags": selection_tags, "restricted_tags": restricted_tags, "descriptive_tags": descriptive_tags}

    all_tags = selection_tags + restricted_tags + descriptive_tags
    created = []
    for tag in all_tags:
        created_term = ensure_term_exists(base_url, user, password, tag, taxonomy="tags")
        created.append(created_term)
        print(f"[taxonomy] Ensured tag '{tag}'")

    return {"created": created}
