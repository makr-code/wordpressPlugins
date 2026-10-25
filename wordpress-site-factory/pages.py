from __future__ import annotations

import json
import re
import shutil
import subprocess
import sys
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


def resolve_parent_page_id(base_url: str, user: str, password: str, parent_value: Optional[Any]) -> Optional[int]:
    if parent_value in (None, "", "0"):
        return None
    if isinstance(parent_value, int):
        return parent_value
    if isinstance(parent_value, dict):
        parent_value = parent_value.get("slug") or parent_value.get("id")
    if isinstance(parent_value, str):
        parent = find_page_by_slug(base_url, user, password, parent_value)
        if parent and isinstance(parent.get("id"), int):
            return parent["id"]
        return None
    return None


def sanitize_shortcode_payload(content: Any) -> Any:
    if not isinstance(content, str):
        return content
    text = content.strip()
    if not text or "themisdb_" not in text:
        return text
    if "<!-- wp:shortcode -->" in text:
        return text

    def wrap_shortcodes(raw: str) -> str:
        matches = re.findall(r"\[[^\]]+\]", raw)
        if not matches:
            return raw
        return "\n\n".join(f"<!-- wp:shortcode -->{match}<!-- /wp:shortcode -->" for match in matches)

    text = re.sub(
        r"(?is)<p\b[^>]*>\s*(\[[^\]]+\](?:\s+\[[^\]]+\])*)\s*</p>",
        lambda match: wrap_shortcodes(match.group(1)),
        text,
    )

    if re.fullmatch(r"(?is)\s*(?:\[[^\]]+\]\s*)+", text):
        return wrap_shortcodes(text)
    return text


def ensure_page(base_url: str, user: str, password: str, page_data: Dict[str, Any]) -> Dict[str, Any]:
    slug = page_data["slug"]
    existing = find_page_by_slug(base_url, user, password, slug)
    parent_id = resolve_parent_page_id(base_url, user, password, page_data.get("parent"))

    payload = {
        "title": page_data["title"],
        "slug": slug,
        "status": page_data.get("status", "publish"),
        "menu_order": page_data.get("menu_order", 0),
        "content": sanitize_shortcode_payload(page_data.get("content", "")),
    }

    template = page_data.get("template")
    if template and str(template).strip():
        candidate = str(template).strip()
        if candidate.lower() in {"default"}:
            payload["template"] = candidate
    # Template values are theme-specific and may be rejected by WordPress when the active
    # theme does not declare them. Omitting them is the safe default for production runs.

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

    blog_page = find_page_by_slug(base_url, user, password, "blog")
    payload = {
        "show_on_front": "page",
        "page_on_front": page["id"],
    }
    if blog_page is not None:
        payload["page_for_posts"] = blog_page["id"]
    return rest_request(base_url, "wp-json/wp/v2/settings", user, password, method="POST", payload=payload)


def ensure_nav_menus(base_url: str, user: str, password: str, dry_run: bool = False) -> List[Dict[str, Any]]:
    repo_root = ROOT.parent
    script_path = repo_root / "tools" / "python" / "create_wordpress_pages.py"
    if not script_path.exists():
        raise FileNotFoundError(f"Navigation generator script not found: {script_path}")

    python_executable = shutil.which("python") or shutil.which("python.exe") or sys.executable
    cmd = [python_executable, str(script_path), "--url", base_url, "--user", user, "--password", password]
    if dry_run:
        cmd.append("--dry-run")

    print("[pages] Ensuring WordPress primary/footer menus and site navigation")
    result = subprocess.run(cmd, cwd=str(repo_root), capture_output=True, text=True)
    if result.returncode != 0:
        detail = result.stderr.strip() or result.stdout.strip() or "unknown error"
        raise RuntimeError(f"Navigation bootstrap failed: {detail}")

    stdout = result.stdout.strip()
    if not stdout:
        return []

    try:
        payload = json.loads(stdout)
    except json.JSONDecodeError:
        return [{"raw_output": stdout}]

    if isinstance(payload, dict) and "pages" in payload:
        return [{"created": payload.get("created", 0), "pages": payload.get("pages", [])}]
    return [payload]


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
        ensure_nav_menus(base_url, user, password, dry_run=True)
        return created

    for page in page_map.values():
        created_page = ensure_page(base_url, user, password, page)
        created.append(created_page)
        print(f"[pages] Created/updated page '{page['title']}'")

    front_page = set_front_page(base_url, user, password, "home")
    if front_page is not None:
        print("[pages] Front page assigned to 'home' and blog page assigned to 'blog'")
    else:
        print("[pages] Front page could not be assigned because the 'home' page is missing")

    menu_result = ensure_nav_menus(base_url, user, password, dry_run=False)
    if menu_result:
        print("[pages] Site navigation recreated and assigned to the active theme locations")

    return created
