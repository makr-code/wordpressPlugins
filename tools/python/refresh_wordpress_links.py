#!/usr/bin/env python3
"""Refresh stale article and media links in WordPress content.

The script scans all published posts/pages, rewrites stale media URLs to the
current WP media URLs and fixes old article links by matching slug/filename.
It is intentionally conservative: it only rewrites URLs when it can resolve a
current canonical target.
"""

from __future__ import annotations

import argparse
import json
import os
import re
from pathlib import Path
from typing import Any, Dict, Iterable, List, Optional
from urllib.parse import urlparse, parse_qs, urlencode, urlunparse

import requests
from bs4 import BeautifulSoup

REPO_ROOT = Path(__file__).resolve().parents[2]
DEFAULT_CONNECTIONS = REPO_ROOT / ".secure" / ".wp_connections.json"


def _read_json_file(path: Path) -> Dict[str, Any]:
    if not path.exists():
        return {}
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
        return data if isinstance(data, dict) else {}
    except Exception:
        return {}


def _resolve_profile(base_url: str, profile_name: Optional[str]) -> Dict[str, Any]:
    config = _read_json_file(DEFAULT_CONNECTIONS)
    profiles = config.get("profiles", {}) if isinstance(config.get("profiles", {}), dict) else {}
    if profile_name and profile_name in profiles:
        candidate = profiles[profile_name]
        if isinstance(candidate, dict):
            selected = {k: v for k, v in candidate.items() if v is not None}
            if selected.get("wp_base"):
                return selected
    if base_url:
        return {"wp_base": base_url}
    return {}


def _windows_to_posix(value: str) -> str:
    return value.replace("\\", "/")


def _slug_from_url(raw_url: str) -> str:
    if not raw_url:
        return ""
    parsed = urlparse(raw_url)
    path = parsed.path.rstrip("/")
    if not path or path == "/":
        return ""
    slug = path.split("/")[-1]
    if not slug:
        return ""
    slug = slug.replace(".html", "").replace(".php", "")
    return slug.strip("/")


def _basename_from_url(raw_url: str) -> str:
    if not raw_url:
        return ""
    parsed = urlparse(raw_url)
    return Path(_windows_to_posix(parsed.path)).name.strip()


def _normalize_url(raw_url: str) -> str:
    if not raw_url:
        return ""
    parsed = urlparse(raw_url)
    host = parsed.netloc.lower().replace("www.", "")
    path = _windows_to_posix(parsed.path)
    query = parsed.query
    if not path or path == "/":
        path = ""
    lower = f"{host}{path}"
    if query:
        lower += "?" + query
    return lower


def _url_without_query(raw_url: str) -> str:
    parsed = urlparse(raw_url)
    return urlunparse(parsed._replace(query=""))


def fetch_wp_items(base_url: str, user: str, password: str, endpoint: str, page_size: int = 100) -> List[Dict[str, Any]]:
    headers = {"Accept": "application/json"}
    all_items: List[Dict[str, Any]] = []
    page = 1
    while True:
        url = f"{base_url.rstrip('/')}/wp-json/wp/v2/{endpoint.lstrip('/')}?per_page={page_size}&page={page}&_fields=id,slug,title,link,content,featured_media,source_url,guid,media_details,media_type"
        response = requests.get(url, auth=(user, password), headers=headers, timeout=60)
        response.raise_for_status()
        data = response.json()
        if not isinstance(data, list):
            break
        if not data:
            break
        all_items.extend(data)
        if len(data) < page_size:
            break
        page += 1
    return all_items


def build_media_lookup(media_items: Iterable[Dict[str, Any]]) -> Dict[str, str]:
    lookup: Dict[str, str] = {}
    for item in media_items:
        if not isinstance(item, dict):
            continue
        url = item.get("source_url") or (item.get("guid", {}) if isinstance(item.get("guid"), dict) else {}).get("rendered") or ""
        if not url:
            continue
        file_name = _basename_from_url(url)
        if not file_name:
            continue
        lookup[file_name.lower()] = url
    return lookup


def build_post_lookup(posts: Iterable[Dict[str, Any]]) -> Dict[str, str]:
    lookup: Dict[str, str] = {}
    for item in posts:
        if not isinstance(item, dict):
            continue
        slug = str(item.get("slug") or "").strip()
        link = str(item.get("link") or "").strip()
        if slug and link:
            lookup[slug.lower()] = link
    return lookup


def rewrite_html_links(content: str, media_lookup: Dict[str, str], slug_lookup: Dict[str, str], base_url: str) -> str:
    if not content or not isinstance(content, str):
        return content or ""

    soup = BeautifulSoup(content, "html.parser")

    for tag in soup.find_all(True):
        for attr_name in ("src", "href", "data-src", "poster"):
            value = tag.get(attr_name)
            if not value or not isinstance(value, str):
                continue
            new_value = resolve_replacement(value, media_lookup, slug_lookup, base_url)
            if new_value and new_value != value:
                tag[attr_name] = new_value

    return str(soup)


def resolve_replacement(raw_value: str, media_lookup: Dict[str, str], slug_lookup: Dict[str, str], base_url: str) -> str:
    if not raw_value or not isinstance(raw_value, str):
        return ""
    cleaned = raw_value.strip()
    if not cleaned:
        return ""

    if cleaned.startswith("data:"):
        return cleaned

    basename = _basename_from_url(cleaned).lower()
    if basename:
        mapped = media_lookup.get(basename)
        if mapped:
            return mapped

    slug = _slug_from_url(cleaned)
    if slug:
        mapped_slug = slug_lookup.get(slug.lower())
        if mapped_slug:
            return mapped_slug

    parsed = urlparse(cleaned)
    if parsed.netloc and parsed.netloc.lower().replace("www.", "") == urlparse(base_url).netloc.lower().replace("www.", ""):
        last_slug = _slug_from_url(cleaned)
        if last_slug:
            canonical = slug_lookup.get(last_slug.lower())
            if canonical:
                return canonical

    return ""


def update_wp_post(base_url: str, user: str, password: str, item: Dict[str, Any], media_lookup: Dict[str, str], slug_lookup: Dict[str, str], dry_run: bool = True) -> Dict[str, Any]:
    post_id = item.get("id")
    if post_id is None:
        return {"id": None, "updated": False}
    original_content = item.get("content", {}).get("rendered") if isinstance(item.get("content"), dict) else item.get("content") or ""
    updated_content = rewrite_html_links(str(original_content), media_lookup, slug_lookup, base_url)
    changed = updated_content != str(original_content)

    link = str(item.get("link") or "").strip()
    item_slug = str(item.get("slug") or "").strip().lower()
    if link:
        candidate = slug_lookup.get(item_slug)
        if candidate and candidate != link:
            link = candidate
            changed = True

    if not dry_run and changed:
        payload: Dict[str, Any] = {"content": updated_content}
        if link and item_slug and item_slug in slug_lookup:
            payload["link"] = link
        response = requests.post(
            f"{base_url.rstrip('/')}/wp-json/wp/v2/posts/{post_id}",
            auth=(user, password),
            json=payload,
            timeout=90,
        )
        response.raise_for_status()
        return {"id": post_id, "updated": True, "content_changed": True, "response_status": response.status_code}

    return {"id": post_id, "updated": changed, "content_changed": changed}


def collect_replacements(base_url: str, user: str, password: str, post_types: List[str]) -> Dict[str, Any]:
    media_items = fetch_wp_items(base_url, user, password, "media")
    media_lookup = build_media_lookup(media_items)
    posts = []
    for post_type in post_types:
        posts.extend(fetch_wp_items(base_url, user, password, post_type))
    slug_lookup = build_post_lookup(posts)
    return {"media_lookup": media_lookup, "slug_lookup": slug_lookup, "posts": posts}


def _arg_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Refresh stale article and media links in WordPress content.")
    parser.add_argument("--base-url", default=os.environ.get("WP_BASE_URL", "http://localhost/wordpress"), help="WordPress base URL")
    parser.add_argument("--user", default=os.environ.get("WP_USER", "test"), help="WordPress user")
    parser.add_argument("--password", default=os.environ.get("WP_APP_PASSWORD", ""), help="WordPress app password")
    parser.add_argument("--profile", default=None, help="Optional WP profiles name from .secure/.wp_connections.json")
    parser.add_argument("--post-type", action="append", default=["posts", "pages"], help="Post type(s) to scan. Can be repeated.")
    parser.add_argument("--dry-run", action="store_true", help="Only show what would change without modifying the site")
    parser.add_argument("--limit", type=int, default=0, help="Optional per-type limit for safe testing")
    return parser


def main() -> int:
    args = _arg_parser().parse_args()

    profile = _resolve_profile(args.base_url, args.profile)
    base_url = profile.get("wp_base") or args.base_url
    user = profile.get("wp_user") or args.user
    password = profile.get("wp_app_password") or args.password

    if not password:
        raise SystemExit("No WordPress application password configured. Set WP_APP_PASSWORD or pass --password.")

    replacements = collect_replacements(base_url, user, password, list(dict.fromkeys(args.post_type)))
    media_lookup = replacements["media_lookup"]
    slug_lookup = replacements["slug_lookup"]
    posts = replacements["posts"]

    if args.limit:
        posts = posts[: args.limit]

    if not posts:
        print("No WordPress posts/pages found to review.")
        return 0

    changed = 0
    for item in posts:
        original = item.get("content", {}).get("rendered") if isinstance(item.get("content"), dict) else item.get("content") or ""
        updated = rewrite_html_links(str(original), media_lookup, slug_lookup, base_url)
        if updated != str(original):
            changed += 1
            print(f"POST {item.get('id')}: {item.get('slug')} -> content links updated")
            if not args.dry_run:
                requests.post(
                    f"{base_url.rstrip('/')}/wp-json/wp/v2/{item.get('type', 'posts') if isinstance(item.get('type'), str) and item.get('type') else 'posts'}/{item.get('id')}",
                    auth=(user, password),
                    json={"content": updated},
                    timeout=90,
                ).raise_for_status()

    print(f"Scanned {len(posts)} items. Changes: {changed}. Dry run: {args.dry_run}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
