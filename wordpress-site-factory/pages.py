from __future__ import annotations

import json
from pathlib import Path
from typing import Any, Dict, List, Optional

from bootstrap import load_json, rest_request


ROOT = Path(__file__).resolve().parent
CONFIG_PATH = ROOT / "config" / "pages.json"


def find_page_by_slug(base_url: str, user: str, password: str, slug: str) -> Optional[Dict[str, Any]]:
    pages = rest_request(base_url, "wp-json/wp/v2/pages?per_page=100", user, password)
    if not isinstance(pages, list):
        return None
    for page in pages:
        if isinstance(page, dict) and page.get("slug") == slug:
            return page
    return None


def ensure_page(base_url: str, user: str, password: str, page_data: Dict[str, Any]) -> Dict[str, Any]:
    slug = page_data["slug"]
    existing = find_page_by_slug(base_url, user, password, slug)
    payload = {
        "title": page_data["title"],
        "slug": slug,
        "status": page_data.get("status", "publish"),
        "menu_order": page_data.get("menu_order", 0),
        "content": page_data.get("content", ""),
    }

    if existing:
        payload["id"] = existing["id"]
        return rest_request(base_url, f"wp-json/wp/v2/pages/{existing['id']}", user, password, method="POST", payload=payload)

    return rest_request(base_url, "wp-json/wp/v2/pages", user, password, method="POST", payload=payload)


def generate_pages(config: Dict[str, Any], dry_run: bool = False) -> List[Dict[str, Any]]:
    wordpress_cfg = config["wordpress"]
    base_url = wordpress_cfg["base_url"]
    user = wordpress_cfg["user"]
    password = wordpress_cfg["app_password"]
    pages_cfg = load_json(ROOT / "config" / "pages.json")

    print("[pages] Generating public page structure")
    created = []
    if dry_run:
        for page in pages_cfg.get("pages", []):
            print(f"[pages] Dry run: would create '{page['title']}' ({page['slug']})")
        return created

    for page in pages_cfg.get("pages", []):
        created_page = ensure_page(base_url, user, password, page)
        created.append(created_page)
        print(f"[pages] Created/updated page '{page['title']}'")

    return created
