#!/usr/bin/env python3
"""Create WordPress pages for the site navigation tree and load HTML template bodies.

The script creates a first-level and second-level page hierarchy for the main site
navigation and populates the page body from reusable HTML templates plus embedded
WordPress plugin shortcodes.

Example:
    python create_wordpress_pages.py --url http://localhost/wordpress --user admin --password secret
    python create_wordpress_pages.py --dry-run
"""

from __future__ import annotations

import argparse
import json
import os
from pathlib import Path
from typing import Dict, Iterable, List, Optional

import requests


ROOT = Path(__file__).resolve().parent
TEMPLATE_ROOT = ROOT / "themisdb-pulse" / "templates"

MAIN_MENU = [
    {
        "title": "Home",
        "slug": "home",
        "template_file": "themisdb-pulse/templates/front-page.html",
        "menu_order": 0,
        "shortcodes": [
            "[themisdb_front_slider posts=\"5\" interval=\"5000\" autoplay=\"yes\" excerpt=\"yes\" date=\"yes\" cat_label=\"yes\"]",
        ],
    },
    {
        "title": "Features",
        "slug": "features",
        "template_file": "themisdb-pulse/templates/page-features.html",
        "menu_order": 10,
        "shortcodes": [
            "[themisdb_v3_feature_cards limit=\"6\" post_types=\"post,page\" category=\"features\" priority_tag=\"frontpage-feature,hero,featured\" excerpt_words=\"24\"]",
        ],
    },
    {
        "title": "Docs",
        "slug": "docs",
        "template_file": "themisdb-pulse/templates/page-docs.html",
        "menu_order": 20,
        "shortcodes": [
            "[themisdb_v3_docs_cards limit=\"8\" post_types=\"post,page\" category=\"documentation,docs,dokumentation\" priority_tag=\"frontpage-documentation,featured,hero\" excerpt_words=\"24\"]",
        ],
        "children": [
            {
                "title": "Getting Started",
                "slug": "docs/getting-started",
                "template_file": "themisdb-pulse/templates/page-docs.html",
                "menu_order": 10,
                "shortcodes": [
                    "[themisdb_v3_docs_cards limit=\"8\" post_types=\"post,page\" category=\"documentation,docs,dokumentation\" priority_tag=\"frontpage-documentation,featured,hero\" excerpt_words=\"24\"]",
                ],
            },
            {
                "title": "API Reference",
                "slug": "docs/api",
                "template_file": "themisdb-pulse/templates/page-docs.html",
                "menu_order": 20,
                "shortcodes": [
                    "[themisdb_wiki file=\"docs/api/OVERVIEW.md\" lang=\"de\" show_toc=\"yes\"]",
                ],
            },
            {
                "title": "Architecture Guide",
                "slug": "docs/architecture",
                "template_file": "themisdb-pulse/templates/page-docs.html",
                "menu_order": 30,
                "shortcodes": [
                    "[themisdb_wiki file=\"docs/architecture/ARCHITECTURE.md\" lang=\"de\" show_toc=\"yes\"]",
                ],
            },
            {
                "title": "SQL Guide",
                "slug": "docs/sql",
                "template_file": "themisdb-pulse/templates/page-docs.html",
                "menu_order": 40,
                "shortcodes": [
                    "[themisdb_wiki file=\"docs/sql/SQL_GUIDE.md\" lang=\"de\" show_toc=\"yes\"]",
                ],
            },
            {
                "title": "Docker Setup",
                "slug": "docs/docker",
                "template_file": "themisdb-pulse/templates/page-docs.html",
                "menu_order": 50,
                "shortcodes": [
                    "[themisdb_wiki file=\"docs/docker/DOCKER_SETUP.md\" lang=\"de\" show_toc=\"yes\"]",
                ],
            },
            {
                "title": "Changelog",
                "slug": "docs/changelog",
                "template_file": "themisdb-pulse/templates/page-docs.html",
                "menu_order": 60,
                "shortcodes": [
                    "[themisdb_wiki file=\"docs/CHANGELOG.md\" lang=\"de\" show_toc=\"yes\"]",
                ],
            },
        ],
    },
    {
        "title": "Downloads",
        "slug": "downloads",
        "template_file": "themisdb-pulse/templates/page-downloads.html",
        "menu_order": 30,
        "shortcodes": [
            "[themisdb_downloads]",
        ],
    },
    {
        "title": "Benchmarks",
        "slug": "benchmarks",
        "template_file": "themisdb-pulse/templates/page.html",
        "menu_order": 40,
        "shortcodes": [
            "[themisdb_benchmark_visualizer]",
        ],
    },
    {
        "title": "Community",
        "slug": "community",
        "template_file": "themisdb-pulse/templates/page-community.html",
        "menu_order": 50,
        "shortcodes": [
            "[themisdb_wiki_nav]",
        ],
    },
    {
        "title": "Blog",
        "slug": "blog",
        "template_file": "themisdb-pulse/templates/page-blog.html",
        "menu_order": 60,
        "shortcodes": [
            "[themisdb_v3_blog_section limit=\"9\" post_types=\"post\" category=\"blog\" priority_tag=\"lesenswert,recommended,editor-pick,hero,frontpage-feature\" excerpt_words=\"24\"]",
        ],
    },
    {
        "title": "Support",
        "slug": "support",
        "template_file": "themisdb-pulse/templates/page-support.html",
        "menu_order": 70,
        "shortcodes": [
            "[themisdb_support_portal]",
        ],
    },
    {
        "title": "Portal",
        "slug": "portal",
        "template_file": "themisdb-pulse/templates/page-portal.html",
        "menu_order": 80,
        "shortcodes": [
            "[themisdb_support_portal]",
        ],
    },
]


DOWNLOAD_ARTIFACT_PAGES = [
    {
        "title": "Downloads Overview",
        "slug": "downloads-overview",
        "content": (
            "<!-- wp:heading {\"level\":2} --><h2>Downloads Overview</h2><!-- /wp:heading -->"
            "\n<!-- wp:paragraph --><p>Alle Build-Artefakte, Images und Bundles an einem Ort.</p><!-- /wp:paragraph -->"
        ),
        "category_slugs": ["downloads"],
        "tag_slugs": ["download", "recommended"],
        "meta": {
            "tv3_download_cta_label": "Downloads ansehen",
            "tv3_download_icon_hint": "overview",
        },
    },
    {
        "title": "Docker Images",
        "slug": "docker-download",
        "content": (
            "<!-- wp:heading {\"level\":2} --><h2>Docker Images</h2><!-- /wp:heading -->"
            "\n<!-- wp:paragraph --><p>Container-Images fuer lokale Entwicklung und produktionsnahe Deployments.</p><!-- /wp:paragraph -->"
        ),
        "category_slugs": ["downloads"],
        "tag_slugs": ["download", "docker", "recommended"],
        "meta": {
            "tv3_download_cta_label": "Docker Download",
            "tv3_download_icon_hint": "docker",
        },
    },
    {
        "title": "Binary Packages",
        "slug": "binary-download",
        "content": (
            "<!-- wp:heading {\"level\":2} --><h2>Binary Packages</h2><!-- /wp:heading -->"
            "\n<!-- wp:paragraph --><p>Direkte Binary-Pakete fuer Linux, Windows und macOS.</p><!-- /wp:paragraph -->"
        ),
        "category_slugs": ["downloads"],
        "tag_slugs": ["download", "binary", "recommended"],
        "meta": {
            "tv3_download_cta_label": "Binary Download",
            "tv3_download_icon_hint": "binary",
        },
    },
    {
        "title": "Compendium Bundles",
        "slug": "compendium-download",
        "content": (
            "<!-- wp:heading {\"level\":2} --><h2>Compendium Bundles</h2><!-- /wp:heading -->"
            "\n<!-- wp:paragraph --><p>Gebuendelte Dokumentation, Referenzen und Assets fuer Teams.</p><!-- /wp:paragraph -->"
        ),
        "category_slugs": ["downloads"],
        "tag_slugs": ["download", "compendium", "recommended"],
        "meta": {
            "tv3_download_cta_label": "Compendium Download",
            "tv3_download_icon_hint": "compendium",
        },
    },
]


def flatten_pages(pages: Iterable[dict], parent: Optional[str] = None) -> List[dict]:
    result: List[dict] = []
    for page in pages:
        item = dict(page)
        item["parent"] = parent
        result.append(item)
        for child in page.get("children", []):
            result.extend(flatten_pages([child], parent=item["slug"]))
    return result


def load_template(path: str) -> str:
    file_path = ROOT / path
    if not file_path.exists():
        raise FileNotFoundError(f"Template not found: {path}")
    return file_path.read_text(encoding="utf-8")


def render_page_content(page: dict) -> str:
    template_path = page.get("template_file")
    if not template_path:
        return page.get("content") or ""

    base = load_template(template_path)
    shortcodes = page.get("shortcodes", [])
    if shortcodes:
        plugin_block = "\n\n".join(
            f"<!-- wp:shortcode -->{code}<!-- /wp:shortcode -->" for code in shortcodes
        )
        return f"{base}\n\n{plugin_block}".strip()
    return base.strip()


def get_wordpress_session(url: str, username: str, password: str) -> requests.Session:
    session = requests.Session()
    session.headers.update({"User-Agent": "ThemisDB WP Page Setup/1.0"})
    session.auth = (username, password)
    return session


def find_term(session: requests.Session, url: str, taxonomy: str, slug: str) -> Optional[dict]:
    endpoint = f"{url.rstrip('/')}/wp-json/wp/v2/{taxonomy}"
    response = session.get(endpoint, params={"slug": slug, "per_page": 100}, timeout=30)
    response.raise_for_status()
    terms = response.json()
    return terms[0] if terms else None


def ensure_term(session: requests.Session, url: str, taxonomy: str, name: str, slug: str) -> int:
    existing = find_term(session, url, taxonomy, slug)
    if existing:
        return int(existing["id"])

    response = session.post(
        f"{url.rstrip('/')}/wp-json/wp/v2/{taxonomy}",
        json={"name": name, "slug": slug},
        timeout=30,
    )
    response.raise_for_status()
    return int(response.json()["id"])


def find_page(session: requests.Session, url: str, slug: str) -> Optional[dict]:
    endpoint = f"{url.rstrip('/')}/wp-json/wp/v2/pages"
    params = {"slug": slug, "per_page": 100}
    response = session.get(endpoint, params=params, timeout=30)
    response.raise_for_status()
    pages = response.json()
    return pages[0] if pages else None


def upsert_page(session: requests.Session, url: str, page: dict, parent_id: Optional[int]) -> dict:
    slug = page["slug"]
    existing = find_page(session, url, slug)
    body = {
        "title": page["title"],
        "slug": slug,
        "status": "publish",
        "template": page.get("template"),
        "content": render_page_content(page),
        "menu_order": int(page.get("menu_order", 0)),
    }
    if parent_id is not None:
        body["parent"] = parent_id

    if existing:
        response = session.request(
            "PUT",
            f"{url.rstrip('/')}/wp-json/wp/v2/pages/{existing['id']}",
            json=body,
            timeout=30,
        )
        response.raise_for_status()
        return response.json()

    response = session.post(
        f"{url.rstrip('/')}/wp-json/wp/v2/pages",
        json=body,
        timeout=30,
    )
    response.raise_for_status()
    return response.json()


def apply_page_taxonomy_and_meta(
    session: requests.Session,
    url: str,
    page_id: int,
    category_ids: List[int],
    tag_ids: List[int],
    meta: Optional[dict],
) -> None:
    taxonomy_payload: Dict[str, object] = {}
    if category_ids:
        taxonomy_payload["categories"] = category_ids
    if tag_ids:
        taxonomy_payload["tags"] = tag_ids

    if taxonomy_payload:
        response = session.request(
            "PUT",
            f"{url.rstrip('/')}/wp-json/wp/v2/pages/{page_id}",
            json=taxonomy_payload,
            timeout=30,
        )
        response.raise_for_status()

    if meta:
        try:
            response = session.request(
                "PUT",
                f"{url.rstrip('/')}/wp-json/wp/v2/pages/{page_id}",
                json={"meta": meta},
                timeout=30,
            )
            response.raise_for_status()
        except requests.HTTPError:
            # Not every WP instance exposes all custom meta keys via REST.
            pass


def ensure_download_artifact_pages(
    session: requests.Session,
    url: str,
    parent_id: Optional[int],
    dry_run: bool,
) -> List[dict]:
    category_map = {
        "downloads": "Downloads",
    }
    tag_map = {
        "download": "Download",
        "docker": "Docker",
        "binary": "Binary",
        "compendium": "Compendium",
        "recommended": "Recommended",
    }

    if dry_run:
        return [
            {
                "title": page["title"],
                "slug": page["slug"],
                "parent": "downloads",
                "seed": "download-artifact",
            }
            for page in DOWNLOAD_ARTIFACT_PAGES
        ]

    category_ids = {
        slug: ensure_term(session, url, "categories", name, slug)
        for slug, name in category_map.items()
    }
    tag_ids = {
        slug: ensure_term(session, url, "tags", name, slug)
        for slug, name in tag_map.items()
    }

    created: List[dict] = []
    for page in DOWNLOAD_ARTIFACT_PAGES:
        page_data = {
            "title": page["title"],
            "slug": page["slug"],
            "content": page["content"],
            "menu_order": 0,
        }
        result = upsert_page(session, url, page_data, parent_id)
        apply_page_taxonomy_and_meta(
            session,
            url,
            int(result["id"]),
            [category_ids[s] for s in page.get("category_slugs", []) if s in category_ids],
            [tag_ids[s] for s in page.get("tag_slugs", []) if s in tag_ids],
            page.get("meta"),
        )
        created.append(result)

    return created


def build_page_tree() -> List[dict]:
    return flatten_pages(MAIN_MENU)


def build_menu_item_plan(pages: Iterable[dict]) -> List[dict]:
    plan: List[dict] = []
    for page in pages:
        plan.append({
            "title": page["title"],
            "slug": page["slug"],
            "menu_order": int(page.get("menu_order", 0)),
            "parent": page.get("parent"),
        })
        for child in page.get("children", []):
            plan.extend(build_menu_item_plan([child]))
    return plan


def ensure_primary_navigation(session: requests.Session, url: str, page_lookup: Dict[str, dict]) -> List[dict]:
    menus_response = session.get(f"{url.rstrip('/')}/wp-json/wp/v2/menus", params={"per_page": 100}, timeout=30)
    menus_response.raise_for_status()
    menus = menus_response.json()
    menu = next(
        (entry for entry in menus if entry.get("slug") in {"primary-navigation", "primary-menu"}),
        None,
    )
    if menu is None:
        create_response = session.post(
            f"{url.rstrip('/')}/wp-json/wp/v2/menus",
            json={"name": "Primary Navigation", "locations": ["primary"]},
            timeout=30,
        )
        create_response.raise_for_status()
        menu = create_response.json()

    menu_id = menu["id"]
    existing_response = session.get(
        f"{url.rstrip('/')}/wp-json/wp/v2/menu-items",
        params={"menus": menu_id, "per_page": 100},
        timeout=30,
    )
    existing_response.raise_for_status()
    menu_items = existing_response.json()
    for stale_item in menu_items:
        title = stale_item.get("title")
        title_text = title.get("rendered") if isinstance(title, dict) else str(title)
        if title_text == "Produkte":
            delete_response = session.delete(
                f"{url.rstrip('/')}/wp-json/wp/v2/menu-items/{stale_item['id']}",
                params={"force": True},
                timeout=30,
            )
            delete_response.raise_for_status()
    existing_by_page = {
        int(item.get("object_id")): item
        for item in menu_items
        if item.get("object") == "page" and item.get("object_id") is not None
    }

    page_plan = build_menu_item_plan(build_page_tree())
    created: List[dict] = []

    for menu_item in page_plan:
        page = page_lookup.get(menu_item["slug"])
        if not page:
            continue

        parent_slug = menu_item["parent"]
        parent_id = None
        if parent_slug:
            parent_page = page_lookup.get(parent_slug)
            if parent_page:
                parent_menu_item = existing_by_page.get(int(parent_page["id"]))
                if parent_menu_item:
                    parent_id = parent_menu_item["id"]

        existing_item = existing_by_page.get(int(page["id"]))
        payload = {
            "title": menu_item["title"],
            "menu_order": int(menu_item["menu_order"]),
            "object": "page",
            "object_id": int(page["id"]),
            "type": "post_type",
            "url": f"{url.rstrip('/')}/{page['slug']}/",
        }
        if parent_id is not None:
            payload["parent"] = parent_id

        if existing_item:
            if existing_item.get("parent") != parent_id:
                update_response = session.request(
                    "PUT",
                    f"{url.rstrip('/')}/wp-json/wp/v2/menu-items/{existing_item['id']}",
                    json=payload,
                    timeout=30,
                )
                update_response.raise_for_status()
                created.append(update_response.json())
            continue

        create_response = session.post(
            f"{url.rstrip('/')}/wp-json/wp/v2/menu-items",
            json={**payload, "menu": menu_id},
            timeout=30,
        )
        create_response.raise_for_status()
        created.append(create_response.json())

    return created


def ensure_pages(url: str, username: str, password: str, dry_run: bool = False) -> List[dict]:
    session = get_wordpress_session(url, username, password)
    pages = build_page_tree()
    created: List[dict] = []
    parent_lookup: Dict[str, Optional[int]] = {}

    for page in pages:
        slug = page["slug"]
        parent_id = None
        if page.get("parent"):
            parent_id = parent_lookup.get(page["parent"])

        if dry_run:
            created.append({
                "title": page["title"],
                "slug": slug,
                "parent": page.get("parent"),
                "menu_order": page.get("menu_order", 0),
                "template": page.get("template_file"),
            })
            continue

        result = upsert_page(session, url, page, parent_id)
        parent_lookup[slug] = result["id"]
        created.append(result)

    artifact_pages = ensure_download_artifact_pages(
        session,
        url,
        parent_lookup.get("downloads"),
        dry_run,
    )
    created.extend(artifact_pages)

    if not dry_run:
        front = find_page(session, url, "home")
        posts = find_page(session, url, "blog")
        if front and posts:
            settings_response = session.post(
                f"{url.rstrip('/')}/wp-json/wp/v2/settings",
                json={
                    "show_on_front": "page",
                    "page_on_front": front["id"],
                    "page_for_posts": posts["id"],
                },
                timeout=30,
            )
            settings_response.raise_for_status()

        page_lookup = {page["slug"]: page for page in created if "slug" in page}
        ensure_primary_navigation(session, url, page_lookup)

    return created


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Create WordPress pages for top-level and second-level navigation.")
    parser.add_argument("--url", default=os.environ.get("WP_URL", "http://localhost/wordpress"), help="WordPress site URL")
    parser.add_argument("--user", default=os.environ.get("WP_USERNAME", "admin"), help="WordPress admin user")
    parser.add_argument("--password", default=os.environ.get("WP_PASSWORD", ""), help="WordPress password")
    parser.add_argument("--dry-run", action="store_true", help="Print the planned pages without creating them")
    return parser.parse_args()


def main() -> int:
    args = parse_args()

    if not args.password:
        raise SystemExit("Missing WordPress password. Set WP_PASSWORD or pass --password.")

    if args.dry_run:
        for page in ensure_pages(args.url, args.user, args.password, dry_run=True):
            print(json.dumps(page, ensure_ascii=False, indent=2))
        return 0

    created = ensure_pages(args.url, args.user, args.password, dry_run=False)
    print(json.dumps({"created": len(created), "pages": [p.get("title") for p in created]}, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
