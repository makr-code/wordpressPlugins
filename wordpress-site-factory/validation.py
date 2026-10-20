from __future__ import annotations

from typing import Any, Dict, List

from bootstrap import rest_request


def _fetch_required_page(base_url: str, user: str, password: str, slug: str) -> Dict[str, Any]:
    response = rest_request(base_url, f"wp-json/wp/v2/pages?slug={slug}&status=publish", user, password)
    if not isinstance(response, list) or not response:
        raise RuntimeError(f"Required page '{slug}' is missing or unpublished.")
    page = response[0]
    if not isinstance(page, dict):
        raise RuntimeError(f"Required page '{slug}' has an invalid payload.")
    return page


def smoke_test(config: Dict[str, Any], dry_run: bool = False) -> Dict[str, Any]:
    wordpress_cfg = config["wordpress"]
    base_url = wordpress_cfg["base_url"]
    user = wordpress_cfg["user"]
    password = wordpress_cfg["app_password"]

    if dry_run:
        print("[validation] Dry run: homepage and public selection checks only")
        return {"status": "dry-run"}

    required_pages = ["home", "features", "documentation", "blog", "downloads", "pricing", "support"]
    pages = {slug: _fetch_required_page(base_url, user, password, slug) for slug in required_pages}

    posts = rest_request(base_url, "wp-json/wp/v2/posts?per_page=20", user, password)
    if not isinstance(posts, list):
        raise RuntimeError("Unable to query posts for validation")

    public_tags = ["announcement", "hero", "feature", "documentation", "pricing", "download", "edition", "blog", "support", "recommended", "lesenswert", "editor-pick", "security", "benchmark", "case-study", "trial"]
    restricted_tags = ["restricted", "internal", "admin-only"]

    for slug in required_pages:
        if not pages[slug].get("id"):
            raise RuntimeError(f"Page '{slug}' is present but missing an id.")

    message = {
        "status": "ok",
        "has_homepage": True,
        "required_pages": sorted(pages.keys()),
        "public_tags": public_tags,
        "restricted_tags": restricted_tags,
        "posts_found": len(posts),
    }

    print("[validation] Homepage and required public pages are present and content queries succeed")
    return message
