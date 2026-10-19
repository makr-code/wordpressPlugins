from __future__ import annotations

import json
from pathlib import Path
from typing import Any, Dict, List, Optional

from bootstrap import load_json, rest_request


ROOT = Path(__file__).resolve().parent
CONFIG_PATH = ROOT / "config" / "pages.json"
SITE_FACTORY_ROOT = ROOT.parent
HTML_PAGE_SPECS = [
    {"slug": "features", "title": "Features", "source": "page-content-blog/features.html"},
    {"slug": "dokumentation", "title": "Dokumentation", "source": "page-content-blog/dokumentation.html"},
    {"slug": "documentation", "title": "Documentation", "source": "page-content-blog/dokumentation.html"},
    {"slug": "pricing", "title": "Pricing", "source": "page-content-blog/pricing.html"},
    {"slug": "support", "title": "Support", "source": "page-content-blog/support.html"},
    {"slug": "download", "title": "Downloads", "source": "page-content-blog/download.html"},
    {"slug": "community", "title": "Community", "source": "page-content-blog/community.html"},
    {"slug": "shop", "title": "Shop", "source": "page-content-blog/shop.html"},
]


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
    parent_slug = page_data.get("parent")
    parent_id = None
    if parent_slug:
        parent_page = find_page_by_slug(base_url, user, password, parent_slug)
        if isinstance(parent_page, dict):
            parent_id_candidate = parent_page.get("id")
            if isinstance(parent_id_candidate, int):
                parent_id = parent_id_candidate

    payload = {
        "title": page_data["title"],
        "slug": slug,
        "status": page_data.get("status", "publish"),
        "menu_order": page_data.get("menu_order", 0),
        "content": page_data.get("content", ""),
    }

    if page_data.get("template"):
        payload["template"] = page_data["template"]
    if parent_id is not None:
        payload["parent"] = parent_id

    if existing:
        payload["id"] = existing["id"]
        return rest_request(base_url, f"wp-json/wp/v2/pages/{existing['id']}", user, password, method="POST", payload=payload)

    return rest_request(base_url, "wp-json/wp/v2/pages", user, password, method="POST", payload=payload)


def load_html_page_content() -> List[Dict[str, Any]]:
    pages: List[Dict[str, Any]] = []
    for spec in HTML_PAGE_SPECS:
        source_path = SITE_FACTORY_ROOT / spec["source"]
        if not source_path.exists():
            continue
        content = source_path.read_text(encoding="utf-8", errors="replace").strip()
        if not content:
            continue
        pages.append({
            "title": spec["title"],
            "slug": spec["slug"],
            "status": "publish",
            "menu_order": 100,
            "content": content,
        })
    return pages


def set_front_page(base_url: str, user: str, password: str, slug: str = "home") -> Optional[Dict[str, Any]]:
    page = find_page_by_slug(base_url, user, password, slug)
    if not page:
        return None

    payload = {
        "show_on_front": "page",
        "page_on_front": page["id"],
    }
    return rest_request(base_url, "wp-json/wp/v2/settings", user, password, method="POST", payload=payload)


def generate_pages(config: Dict[str, Any], dry_run: bool = False) -> List[Dict[str, Any]]:
    wordpress_cfg = config["wordpress"]
    base_url = wordpress_cfg["base_url"]
    user = wordpress_cfg["user"]
    password = wordpress_cfg["app_password"]
    pages_cfg = load_json(ROOT / "config" / "pages.json")

    print("[pages] Generating public page structure")
    page_map: Dict[str, Dict[str, Any]] = {}
    for page in pages_cfg.get("pages", []):
        page_map[page["slug"]] = page

    for page in load_html_page_content():
        page_map[page["slug"]] = page

    created = []
    if dry_run:
        for page in page_map.values():
            print(f"[pages] Dry run: would create '{page['title']}' ({page['slug']})")
        return created

    for page in page_map.values():
        created_page = ensure_page(base_url, user, password, page)
        created.append(created_page)
        print(f"[pages] Created/updated page '{page['title']}'")

    front_page = set_front_page(base_url, user, password, "home")
    if front_page is not None:
        print("[pages] Front page assigned to 'home'")
    else:
        print("[pages] Front page could not be assigned because the 'home' page is missing")

    return created
