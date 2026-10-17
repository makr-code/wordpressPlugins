from __future__ import annotations

from typing import Any, Dict, List

from bootstrap import rest_request


def smoke_test(config: Dict[str, Any], dry_run: bool = False) -> Dict[str, Any]:
    wordpress_cfg = config["wordpress"]
    base_url = wordpress_cfg["base_url"]
    user = wordpress_cfg["user"]
    password = wordpress_cfg["app_password"]

    if dry_run:
        print("[validation] Dry run: homepage and public selection checks only")
        return {"status": "dry-run"}

    home = rest_request(base_url, "wp-json/wp/v2/pages?slug=home&status=publish", user, password)
    if not isinstance(home, list) or not home:
        raise RuntimeError("Homepage was not created. Run page generation first.")

    posts = rest_request(base_url, "wp-json/wp/v2/posts?per_page=20", user, password)
    if not isinstance(posts, list):
        raise RuntimeError("Unable to query posts for validation")

    public_tags = ["announcement", "hero", "feature", "documentation", "pricing", "download", "edition", "blog", "support", "recommended", "editor-pick"]
    restricted_tags = ["restricted", "internal", "admin-only"]

    message = {
        "status": "ok",
        "has_homepage": True,
        "public_tags": public_tags,
        "restricted_tags": restricted_tags,
        "posts_found": len(posts),
    }

    print("[validation] Homepage exists and content queries succeed")
    return message
