from __future__ import annotations

import argparse
import hashlib
import html as html_lib
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
import time

from PIL import Image, ImageOps
from datetime import datetime, timezone
from pathlib import Path, PurePosixPath
from typing import Any, Dict, Iterable, List, Optional
from urllib.parse import quote, urlparse

import requests

ROOT = Path(__file__).resolve().parents[1]
DOCS_CACHE_PATH = ROOT / "output" / "docs_cache.json"
TAXONOMY_PATH = ROOT / "config" / "taxonomy.json"
SITE_CONFIG_PATH = ROOT.parent / ".secure" / "site.json"
LOCAL_SITE_CONFIG_PATH = ROOT.parent / ".secure" / "site.local.json"
WP_CONNECTIONS_PATH = ROOT.parent / ".secure" / ".wp_connections.json"
BLOG_SOURCES_PATH = ROOT.parent / "page-content-blog" / "sources"
LEGACY_IMPORT_STATE_PATH = ROOT.parent / "page-content-blog" / ".wp_import_state.json"
REST_TIMEOUT_SECONDS = int(os.environ.get("WP_REST_TIMEOUT", "30"))
REST_MAX_RETRIES = int(os.environ.get("WP_REST_RETRIES", "4"))
REST_RETRY_BACKOFF = float(os.environ.get("WP_REST_RETRY_BACKOFF", "2.0"))
_IMPORT_MEDIA_CACHE: Dict[str, Any] = {}
_IMPORT_IMAGE_CACHE: Dict[str, Any] = {}

IMAGE_MEDIA_EXTENSIONS = {".png", ".jpg", ".jpeg", ".webp", ".gif", ".bmp", ".tif", ".tiff", ".svg"}
AUDIO_MEDIA_EXTENSIONS = {".mp3", ".wav", ".ogg", ".m4a", ".aac", ".flac", ".opus", ".m4b"}
VIDEO_MEDIA_EXTENSIONS = {".mp4", ".webm", ".mov", ".m4v", ".avi", ".mkv", ".wmv", ".flv", ".3gp"}
DOCUMENT_MEDIA_EXTENSIONS = {".pdf", ".doc", ".docx", ".ppt", ".pptx", ".xls", ".xlsx"}


def load_site_config(required: bool = True) -> Dict[str, Any]:
    if not SITE_CONFIG_PATH.exists():
        if required:
            raise FileNotFoundError(f"Missing secure config at {SITE_CONFIG_PATH}. Place WordPress credentials in .secure/site.json.")
        return {}
    try:
        payload = json.loads(SITE_CONFIG_PATH.read_text(encoding="utf-8"))
    except Exception:
        if required:
            raise RuntimeError(f"Failed to parse secure config at {SITE_CONFIG_PATH}.")
        return {}
    return payload if isinstance(payload, dict) else {}


def get_runtime_site_setting(key: str, default: Any = None) -> Any:
    config = load_site_config(required=False)
    wordpress = config.get("wordpress", {}) if isinstance(config, dict) else {}
    if not isinstance(wordpress, dict):
        return default
    value = wordpress.get(key, default)
    return default if value is None else value


def is_local_wordpress_url(base_url: str) -> bool:
    host = (urlparse(str(base_url or "")).hostname or "").strip().lower()
    return host in {"localhost", "127.0.0.1"}


def load_optional_site_config(path: Path) -> Dict[str, Any]:
    if not path.exists():
        return {}
    try:
        payload = json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return {}
    return payload if isinstance(payload, dict) else {}


def preferred_default_wp_connection() -> Dict[str, str]:
    connections_config = load_optional_site_config(WP_CONNECTIONS_PATH)
    profiles = connections_config.get("profiles", {}) if isinstance(connections_config, dict) else {}
    default_profile_name = str(connections_config.get("default") or "").strip() if isinstance(connections_config, dict) else ""
    if profiles and default_profile_name:
        selected = profiles.get(default_profile_name, {})
        if isinstance(selected, dict):
            base_url = str(selected.get("wp_base") or "").strip()
            if is_local_wordpress_url(base_url):
                return {
                    "base_url": base_url,
                    "user": str(selected.get("wp_user") or "").strip(),
                    "password": str(selected.get("wp_app_password") or "").strip(),
                    "rest_route_style": str(selected.get("rest_route_style") or "wp-json").strip() or "wp-json",
                }
    local_config = load_optional_site_config(LOCAL_SITE_CONFIG_PATH).get("wordpress", {})
    if isinstance(local_config, dict):
        base_url = str(local_config.get("base_url") or "").strip()
        if is_local_wordpress_url(base_url):
            return {
                "base_url": base_url,
                "user": str(local_config.get("user") or "").strip(),
                "password": str(local_config.get("app_password") or "").strip(),
                "rest_route_style": str(local_config.get("rest_route_style") or "wp-json").strip() or "wp-json",
            }
    return {
        "base_url": str(get_runtime_site_setting("base_url", "http://localhost/wordpress") or "http://localhost/wordpress").strip(),
        "user": str(get_runtime_site_setting("user", "test") or "test").strip(),
        "password": str(get_runtime_site_setting("app_password", "") or "").strip(),
        "rest_route_style": str(get_runtime_site_setting("rest_route_style", "wp-json") or "wp-json").strip() or "wp-json",
    }


_DEFAULT_CONNECTION = preferred_default_wp_connection()
DEFAULT_BASE_URL = os.environ.get("WP_BASE_URL") or _DEFAULT_CONNECTION["base_url"]
DEFAULT_USER = os.environ.get("WP_USER") or _DEFAULT_CONNECTION["user"]
DEFAULT_PASSWORD = os.environ.get("WP_APP_PASSWORD") or _DEFAULT_CONNECTION["password"]
DEFAULT_REST_ROUTE_STYLE = os.environ.get("WP_REST_ROUTE_STYLE") or _DEFAULT_CONNECTION["rest_route_style"]
RUNTIME_REST_ROUTE_STYLE = str(DEFAULT_REST_ROUTE_STYLE).strip().lower()


def normalize_app_password(value: str) -> str:
    text = str(value or "").strip()
    return re.sub(r"\s+", "", text)


def resolve_runtime_credentials(base_url: str, user: str, password: str) -> Dict[str, str]:
    resolved_user = str(user or "")
    resolved_password = str(password or "")
    resolved_rest_route_style = RUNTIME_REST_ROUTE_STYLE

    if not is_local_wordpress_url(base_url):
        resolved_password = normalize_app_password(resolved_password)
        return {"user": resolved_user, "password": normalize_app_password(resolved_password), "rest_route_style": resolved_rest_route_style}

    local_env_user = str(os.environ.get("WP_LOCAL_USER") or "").strip()
    local_env_password = str(os.environ.get("WP_LOCAL_APP_PASSWORD") or "").strip()
    local_config = load_optional_site_config(LOCAL_SITE_CONFIG_PATH).get("wordpress", {})
    local_cfg_user = str(local_config.get("user") or "").strip() if isinstance(local_config, dict) else ""
    local_cfg_password = str(local_config.get("app_password") or "").strip() if isinstance(local_config, dict) else ""
    local_cfg_route_style = str(local_config.get("rest_route_style") or "").strip().lower() if isinstance(local_config, dict) else ""

    connections_config = load_optional_site_config(WP_CONNECTIONS_PATH)
    profiles = connections_config.get("profiles", {}) if isinstance(connections_config, dict) else {}
    default_profile_name = str(connections_config.get("default") or "").strip() if isinstance(connections_config, dict) else ""
    profile_name = str(os.environ.get("WP_CONNECTION_PROFILE") or default_profile_name).strip()
    selected_profile = profiles.get(profile_name, {}) if isinstance(profiles, dict) and profile_name else {}
    if not isinstance(selected_profile, dict):
        selected_profile = {}
    profile_base = str(selected_profile.get("wp_base") or "").strip()
    profile_user = str(selected_profile.get("wp_user") or "").strip()
    profile_password = str(selected_profile.get("wp_app_password") or "").strip()
    profile_route_style = str(selected_profile.get("rest_route_style") or "").strip().lower()
    profile_matches_local = is_local_wordpress_url(profile_base)

    if profile_matches_local and (not resolved_user or resolved_user == str(DEFAULT_USER)) and profile_user:
        resolved_user = profile_user
    if profile_matches_local and (not resolved_password or resolved_password == str(DEFAULT_PASSWORD)) and profile_password:
        resolved_password = profile_password
    if profile_matches_local and profile_route_style:
        resolved_rest_route_style = profile_route_style

    if (not resolved_user or resolved_user == str(DEFAULT_USER)) and local_env_user:
        resolved_user = local_env_user
    if (not resolved_password or resolved_password == str(DEFAULT_PASSWORD)) and local_env_password:
        resolved_password = local_env_password

    if (not resolved_user or resolved_user == str(DEFAULT_USER)) and local_cfg_user:
        resolved_user = local_cfg_user
    if (not resolved_password or resolved_password == str(DEFAULT_PASSWORD)) and local_cfg_password:
        resolved_password = local_cfg_password
    if local_cfg_route_style:
        resolved_rest_route_style = local_cfg_route_style

    return {"user": resolved_user, "password": resolved_password, "rest_route_style": resolved_rest_route_style}


def make_json_safe(value: Any) -> Any:
    if value is None or isinstance(value, (str, int, float, bool)):
        return value
    if isinstance(value, (Path, PurePosixPath)):
        return str(value)
    if isinstance(value, dict):
        return {str(key): make_json_safe(item) for key, item in value.items()}
    if isinstance(value, (list, tuple)):
        return [make_json_safe(item) for item in value]
    if isinstance(value, set):
        return [make_json_safe(item) for item in sorted(value, key=lambda item: str(item))]
    return str(value)


def load_json(path: Path) -> Dict[str, Any]:
    if not path.exists():
        return {"documents": []}
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return {"documents": []}


def load_legacy_import_state() -> None:
    global _IMPORT_MEDIA_CACHE, _IMPORT_IMAGE_CACHE
    if not LEGACY_IMPORT_STATE_PATH.exists():
        return
    try:
        state = json.loads(LEGACY_IMPORT_STATE_PATH.read_text(encoding="utf-8"))
    except Exception:
        return
    if isinstance(state, dict):
        media_cache = state.get("_media_cache")
        image_cache = state.get("_image_cache")
        if isinstance(media_cache, dict):
            _IMPORT_MEDIA_CACHE.update(media_cache)
        if isinstance(image_cache, dict):
            _IMPORT_IMAGE_CACHE.update(image_cache)


def save_legacy_import_state() -> None:
    try:
        state: Dict[str, Any] = {}
        if LEGACY_IMPORT_STATE_PATH.exists():
            try:
                parsed = json.loads(LEGACY_IMPORT_STATE_PATH.read_text(encoding="utf-8"))
                if isinstance(parsed, dict):
                    state = parsed
            except Exception:
                state = {}
        state["_media_cache"] = dict(_IMPORT_MEDIA_CACHE)
        state["_image_cache"] = dict(_IMPORT_IMAGE_CACHE)
        tmp_path = LEGACY_IMPORT_STATE_PATH.with_suffix(".json.tmp")
        tmp_path.write_text(json.dumps(state, ensure_ascii=False, indent=2), encoding="utf-8")
        tmp_path.replace(LEGACY_IMPORT_STATE_PATH)
    except Exception:
        pass


load_legacy_import_state()


def slugify_text(value: str) -> str:
    normalized = re.sub(r"[^a-z0-9]+", "-", str(value or "").lower())
    normalized = re.sub(r"-{2,}", "-", normalized).strip("-")
    return normalized or "document"


def extract_markdown_title(markdown_path: Path) -> str:
    try:
        for line in markdown_path.read_text(encoding="utf-8", errors="replace").splitlines():
            stripped = line.strip()
            if stripped.startswith("# "):
                return stripped[2:].strip()
    except Exception:
        return ""
    return ""


def load_legacy_status_hint(page_content_root: Path, folder_name: str) -> Dict[str, str]:
    status_file = page_content_root / "output" / folder_name / "status.yaml"
    if not status_file.exists():
        return {}
    try:
        text = status_file.read_text(encoding="utf-8", errors="replace")
    except Exception:
        return {}

    def _find(pattern: str) -> str:
        match = re.search(pattern, text, flags=re.MULTILINE)
        return match.group(1).strip() if match else ""

    return {
        "wp_slug": _find(r"^\s*wp_slug:\s*([^\n]+)\s*$"),
        "wp_id": _find(r"^\s*wp_id:\s*([0-9]+)\s*$"),
        "title": _find(r"^\s*title:\s*(.+?)\s*$"),
        "date": _find(r"^\s*date:\s*([0-9]{4}-[0-9]{2}-[0-9]{2})\s*$"),
    }


def build_blog_documents_from_sources(sources_root: Path) -> List[Dict[str, Any]]:
    documents: List[Dict[str, Any]] = []
    if not sources_root.exists():
        return documents

    page_content_root = sources_root.parent
    folder_modes = [path for path in sorted(sources_root.iterdir()) if path.is_dir() and not path.name.startswith("_")]

    def _build_entry_from_path(article_path: Path, folder_label: str = "", meta: Optional[Dict[str, Any]] = None) -> Optional[Dict[str, Any]]:
        if not article_path.is_file() or article_path.name.endswith((".duplicate", ".bak", ".bak2")):
            return None
        content = article_path.read_text(encoding="utf-8", errors="replace")
        legacy_hint = load_legacy_status_hint(page_content_root, folder_label) if folder_label else {}
        derived_title = extract_markdown_title(article_path)
        title = str((meta or {}).get("title") or derived_title or legacy_hint.get("title") or article_path.stem).strip() or article_path.stem
        slug = str((meta or {}).get("slug") or legacy_hint.get("wp_slug") or "").strip() or slugify_text(title)
        last_modified = datetime.fromtimestamp(article_path.stat().st_mtime, tz=timezone.utc).isoformat()
        derived_tags = merge_tag_list((meta or {}).get("tags", []), source_match_tags_for_path(article_path, title), source_match_tags_for_path(folder_label, title))
        category = str((meta or {}).get("category") or "").strip() or source_match_category_for_path(article_path, title) or "Blog"
        tags = derived_tags or coerce_term_list((meta or {}).get("tags", []))
        excerpt = str((meta or {}).get("excerpt") or "").strip()
        status = str((meta or {}).get("status") or "publish").strip().lower()
        published_date = str((meta or {}).get("date") or legacy_hint.get("date") or "").strip()
        published_at = normalize_iso_datetime(f"{published_date}T09:00:00+00:00") if published_date else last_modified

        return {
            "title": title,
            "slug": slug,
            "kind": "blog",
            "category": category,
            "tags": tags,
            "excerpt": excerpt,
            "status": status,
            "source_path": str(article_path),
            "relative_path": str(article_path),
            "extension": "md",
            "size": len(content.encode("utf-8", errors="ignore")),
            "hash": hashlib.sha256(content.encode("utf-8")).hexdigest(),
            "last_modified": last_modified,
            "source_class": "public",
            "pipeline": "page-content-blog",
            "allow_unrestricted_tags": True,
            "legacy_wp_id": legacy_hint.get("wp_id") or "",
            "metadata": {
                "author": (meta or {}).get("author") or "ThemisDB",
                "coauthors": (meta or {}).get("coauthors") or [],
                "language": (meta or {}).get("language") or "de",
                "source_origin": "page-content-blog/sources",
                "source_path": str(article_path),
                "summary": excerpt,
                "status": status,
                "category": category,
                "date": published_at,
                "published_at": published_at,
                "legacy_wp_id": legacy_hint.get("wp_id") or "",
            },
            "suitability": {
                "score": 100,
                "readiness": "ready",
                "recommendation": "import",
                "review_flags": [],
            },
        }

    if folder_modes:
        for folder in folder_modes:
            article_path = folder / "article.md"
            meta_path = folder / "meta.json"
            meta: Dict[str, Any] = {}

            if article_path.exists() and meta_path.exists():
                try:
                    meta = json.loads(meta_path.read_text(encoding="utf-8"))
                except Exception:
                    meta = {}
            else:
                legacy_candidates = [
                    path
                    for path in sorted(folder.glob("*.md"))
                    if path.is_file() and not path.name.endswith((".duplicate", ".bak", ".bak2"))
                ]
                if not legacy_candidates:
                    continue
                article_path = legacy_candidates[0]
                meta = {}

            entry = _build_entry_from_path(article_path, folder_label=folder.name, meta=meta)
            if entry is not None:
                documents.append(entry)
        return documents

    for path in sorted(sources_root.rglob("*")):
        if not path.is_file():
            continue
        if path.suffix.lower() not in {".md", ".markdown", ".txt"}:
            continue
        if path.name.endswith((".duplicate", ".bak", ".bak2")):
            continue
        if path.name == "README.md":
            continue
        if any(part.startswith("_") for part in path.relative_to(sources_root).parts[:-1]):
            continue

        entry = _build_entry_from_path(path, folder_label=path.parent.name)
        if entry is not None:
            documents.append(entry)
    return documents


def load_taxonomy() -> Dict[str, List[str]]:
    if not TAXONOMY_PATH.exists():
        return {"selection_tags": [], "restricted_tags": [], "descriptive_tags": []}
    try:
        data = json.loads(TAXONOMY_PATH.read_text(encoding="utf-8"))
    except Exception:
        return {"selection_tags": [], "restricted_tags": [], "descriptive_tags": []}
    return {
        "selection_tags": data.get("selection_tags", []),
        "restricted_tags": data.get("restricted_tags", []),
        "descriptive_tags": data.get("descriptive_tags", []),
    }


SOURCE_MATCH_TABLE: List[Dict[str, Any]] = [
    {"match": ["page-content-blog", "sources"], "tags": ["blog"], "category": "blog"},
    {"match": ["_unmapped", "_docs", "docs", "wiki", "documentation", "handbuch", "manual"], "tags": ["documentation"], "category": "documentation"},
    {"match": ["benchmark", "benchmarks", "vergleich"], "tags": ["benchmark"], "category": "benchmark"},
    {"match": ["podcast", "audio", "episode"], "tags": ["podcast"], "category": "podcast"},
    {"match": ["roadmap", "architecture", "design"], "tags": ["architecture"], "category": "architecture"},
    {"match": ["security", "security-review", "threat"], "tags": ["security"], "category": "security"},
    {"match": ["guide", "how-to", "tutorial", "anleitung"], "tags": ["guide"], "category": "documentation"},
    {"match": ["analysis", "research", "insight", "evaluierung"], "tags": ["analysis"], "category": "documentation"},
]


def normalize_source_lookup_value(value: Any) -> str:
    return str(value or "").replace("\\", "/").lower().strip()


def merge_tag_list(*parts: Iterable[str]) -> List[str]:
    merged: List[str] = []
    seen = set()
    for part in parts:
        for item in coerce_term_list(part):
            value = str(item).strip().lower().replace("_", "-")
            if not value or value in seen:
                continue
            seen.add(value)
            merged.append(value)
    return merged


def source_match_tags_for_path(source_path: Any, title: str = "") -> List[str]:
    resolved = normalize_source_lookup_value(source_path)
    text = f"{resolved} {normalize_source_lookup_value(title)}"
    if not text:
        return []

    matched_tags: List[str] = []
    for rule in SOURCE_MATCH_TABLE:
        tokens = [normalize_source_lookup_value(token) for token in rule.get("match", [])]
        if not tokens:
            continue
        if any(token in text for token in tokens):
            matched_tags.extend(coerce_term_list(rule.get("tags", [])))
    return merge_tag_list(matched_tags)


def source_match_category_for_path(source_path: Any, title: str = "") -> str:
    resolved = normalize_source_lookup_value(source_path)
    text = f"{resolved} {normalize_source_lookup_value(title)}"
    if not text:
        return "documentation"

    for rule in SOURCE_MATCH_TABLE:
        tokens = [normalize_source_lookup_value(token) for token in rule.get("match", [])]
        if any(token in text for token in tokens):
            category = str(rule.get("category") or "documentation").strip().lower().replace("_", "-")
            if category:
                return category
    return "documentation"


def build_wp_url(base_url: str, path: str) -> str:
    return build_wp_url_for_style(base_url, path, str(RUNTIME_REST_ROUTE_STYLE).strip().lower())


def build_wp_url_for_style(base_url: str, path: str, style: str) -> str:
    normalized = path.lstrip("/")
    if normalized.startswith("wp-json/"):
        normalized = normalized[len("wp-json/"):]
    if str(style).strip().lower() == "query":
        return base_url.rstrip("/") + "/?rest_route=/" + normalized
    return base_url.rstrip("/") + "/" + path.lstrip("/")


def rest_request(base_url: str, user: str, password: str, path: str, method: str = "GET", payload: Optional[Dict[str, Any]] = None) -> Any:
    auth = (user, password) if user and password else None
    response = None
    preferred_style = str(RUNTIME_REST_ROUTE_STYLE).strip().lower()
    styles = [preferred_style]
    if preferred_style == "query":
        styles.append("wp-json")
    elif preferred_style == "wp-json":
        styles.append("query")

    for style_index, style in enumerate(styles):
        url = build_wp_url_for_style(base_url, path, style)
        response = None
        for attempt in range(1, REST_MAX_RETRIES + 1):
            try:
                response = requests.request(
                    method=method,
                    url=url,
                    auth=auth,
                    json=payload,
                    timeout=REST_TIMEOUT_SECONDS,
                )
            except requests.exceptions.RequestException as exc:
                if attempt >= REST_MAX_RETRIES:
                    if style_index + 1 < len(styles):
                        response = None
                        break
                    raise RuntimeError(
                        f"REST call failed after {REST_MAX_RETRIES} attempts: {method} {url} -> {exc}"
                    ) from exc
                sleep_seconds = min(REST_RETRY_BACKOFF ** (attempt - 1), 12.0)
                time.sleep(sleep_seconds)
                continue

            if response.status_code >= 500 and attempt < REST_MAX_RETRIES:
                sleep_seconds = min(REST_RETRY_BACKOFF ** (attempt - 1), 12.0)
                time.sleep(sleep_seconds)
                continue
            break

        if response is None:
            continue

        if response.status_code in (401, 404) and auth and style_index + 1 < len(styles):
            body = {}
            try:
                body = response.json()
            except ValueError:
                body = {}
            code = str(body.get("code") or "").strip()
            if code in {"rest_not_logged_in", "rest_no_route"}:
                response = None
                continue
        break

    if response is None:
        raise RuntimeError(f"REST call failed without response: {method} {build_wp_url(base_url, path)}")
    if response.status_code >= 400:
        raise RuntimeError(f"REST call failed: {method} {response.url} -> {response.status_code}: {response.text[:500]}")
    if not response.content:
        return None
    try:
        return response.json()
    except ValueError:
        return response.text


def fetch_posts_by_source_path(base_url: str, user: str, password: str, source_path: str) -> List[Dict[str, Any]]:
    if not source_path:
        return []
    candidates: List[Dict[str, Any]] = []
    query_suffix = "&status=any&context=edit"
    for query in (
        "wp-json/wp/v2/posts?search=" + quote(source_path) + "&per_page=20" + query_suffix,
        "wp-json/wp/v2/posts?meta_key=themisdb_source_path&meta_value=" + quote(source_path) + "&per_page=20" + query_suffix,
    ):
        try:
            posts = rest_request(base_url, user, password, query)
        except Exception:
            continue
        if not isinstance(posts, list):
            continue
        for item in posts:
            if not isinstance(item, dict):
                continue
            item_source = str(item.get("meta", {}).get("themisdb_source_path") or item.get("themisdb_source_path") or item.get("meta_input", {}).get("themisdb_source_path") or "").strip()
            if not item_source and item.get("meta"):
                item_source = str(item["meta"].get("themisdb_source_path") or "").strip()
            if item_source and item_source.replace("\\", "/") == source_path.replace("\\", "/"):
                candidates.append(item)
                continue
            if "source_path" in str(item.get("slug") or ""):
                continue
            if source_path.lower() in str(item.get("link") or "").lower():
                candidates.append(item)
    deduped: List[Dict[str, Any]] = []
    seen_ids = set()
    for item in candidates:
        item_id = item.get("id")
        if item_id is None or item_id in seen_ids:
            continue
        seen_ids.add(item_id)
        deduped.append(item)
    return deduped


def existing_wp_doc(base_url: str, user: str, password: str, source_path: str, slug: str, title: Optional[str] = None) -> Optional[Dict[str, Any]]:
    source_path = str(source_path or "").strip()
    slug = str(slug or "").strip()
    title = str(title or "").strip()

    matches = fetch_posts_by_source_path(base_url, user, password, source_path) if source_path else []
    for post in matches:
        post_slug = str(post.get("slug") or "").strip()
        post_title = str(post.get("title", {}).get("rendered") if isinstance(post.get("title"), dict) else post.get("title") or "").strip()
        if post_slug == slug or post_slug.startswith(slug + "-") or (title and post_title.lower() == title.lower()):
            return post

    if slug:
        try:
            posts = rest_request(
                base_url,
                user,
                password,
                "wp-json/wp/v2/posts?slug=" + quote(slug) + "&per_page=20&status=any&context=edit",
            )
        except Exception:
            posts = []
        if isinstance(posts, list):
            for post in posts:
                if isinstance(post, dict):
                    post_slug = str(post.get("slug") or "").strip()
                    if post_slug == slug or post_slug.startswith(slug + "-"):
                        return post

    if title:
        try:
            posts = rest_request(
                base_url,
                user,
                password,
                "wp-json/wp/v2/posts?search=" + quote(title) + "&per_page=20&status=any&context=edit",
            )
        except Exception:
            posts = []
        if isinstance(posts, list):
            for post in posts:
                if not isinstance(post, dict):
                    continue
                post_slug = str(post.get("slug") or "").strip()
                post_title = str(post.get("title", {}).get("rendered") if isinstance(post.get("title"), dict) else post.get("title") or "").strip()
                if (post_slug == slug or post_slug.startswith(slug + "-") or post_title.lower() == title.lower()):
                    return post

    return None


def fetch_wp_post_by_id(base_url: str, user: str, password: str, post_id: int) -> Optional[Dict[str, Any]]:
    try:
        post = rest_request(base_url, user, password, f"wp-json/wp/v2/posts/{int(post_id)}")
    except Exception:
        return None
    return post if isinstance(post, dict) else None


def ensure_taxonomy_terms(tags: Iterable[str]) -> List[str]:
    taxonomy = load_taxonomy()
    allowed = set(taxonomy.get("selection_tags", [])) | set(taxonomy.get("restricted_tags", [])) | set(taxonomy.get("descriptive_tags", []))
    selected: List[str] = []
    seen = set()
    for tag in coerce_term_list(tags):
        value = str(tag).strip().lower().replace("_", "-")
        if not value or value in seen or value not in allowed:
            continue
        selected.append(value)
        seen.add(value)
    return selected


def primary_category_for_tags(tags: Iterable[str]) -> str:
    taxonomy = load_taxonomy()
    selection = taxonomy.get("selection_tags", [])
    descriptive = taxonomy.get("descriptive_tags", [])
    ordered = list(selection) + list(descriptive)
    ranking = {tag: index for index, tag in enumerate(ordered)}
    for tag in coerce_term_list(tags):
        candidate = str(tag).strip().lower().replace("_", "-")
        if candidate in ranking:
            return candidate
    return "documentation"


def _has_audio_or_podcast_signal(value: Any) -> bool:
    normalized = str(value or "").lower()
    if not normalized:
        return False
    tokens = (
        "podcast",
        "audio",
        "episode",
        "audio_url",
        "podcast_files",
        ".mp3",
        ".wav",
        ".m4a",
        ".ogg",
        "podcast episode",
    )
    return any(token in normalized for token in tokens)


def resolve_document_category_names(doc: Dict[str, Any], tags: Iterable[str], explicit_category: str = "") -> List[str]:
    explicit = str(explicit_category or "").strip()
    if explicit:
        return [explicit]

    source_origin = str(doc.get("source_origin") or doc.get("metadata", {}).get("source_origin") if isinstance(doc.get("metadata"), dict) else "").lower()
    pipeline = str(doc.get("pipeline") or "").lower()
    source_path = str(doc.get("source_path") or "").lower()
    title = str(doc.get("title") or "").lower()
    tag_values = [str(tag).strip().lower().replace("_", "-") for tag in coerce_term_list(tags) if str(tag).strip()]
    metadata_values = []
    if isinstance(doc.get("metadata"), dict):
        metadata_values.extend(str(value) for value in doc["metadata"].values())
    metadata_text = " ".join(metadata_values)
    source_text = read_source_text(doc) if callable(read_source_text) else ""
    route_text = " ".join([source_path, title, source_text.lower(), metadata_text.lower(), " ".join(tag_values)])
    matched_categories: List[str] = []

    for rule in SOURCE_MATCH_TABLE:
        tokens = [normalize_source_lookup_value(token) for token in rule.get("match", [])]
        if not tokens:
            continue
        if any(token in route_text for token in tokens):
            category = str(rule.get("category") or "").strip().lower().replace("_", "-")
            if category:
                matched_categories.append(category)

    if "page-content-blog" in source_origin or "page-content-blog" in source_path or pipeline == "page-content-blog":
        matched_categories.append("blog")
    if _has_audio_or_podcast_signal(route_text):
        matched_categories.append("podcast")
    if "help" in route_text or "hilfe" in route_text or "faq" in route_text or "anleitung" in route_text or "guide" in route_text:
        matched_categories.append("help")
    if "dokumentation" in route_text or "documentation" in route_text or "wiki" in route_text or "manual" in route_text or "wikki" in route_text:
        matched_categories.append("documentation")
    if "blog" in tag_values:
        matched_categories.append("blog")
    for preferred in ("documentation", "feature", "security", "benchmark", "architecture"):
        if preferred in tag_values:
            matched_categories.append(preferred)
    if "docs" in source_origin or "docs" in source_path or "documentation" in source_origin or "documentation" in source_path:
        matched_categories.append("documentation")

    if matched_categories:
        ordered = []
        seen = set()
        for category in matched_categories + [primary_category_for_tags(tag_values) or "documentation"]:
            normalized = str(category).strip().lower().replace("_", "-")
            if not normalized or normalized in seen:
                continue
            seen.add(normalized)
            ordered.append(normalized)
        return ordered

    return [primary_category_for_tags(tag_values) or "documentation"]


def find_existing_term_id(base_url: str, user: str, password: str, taxonomy: str, term_name: str) -> Optional[int]:
    slug = str(term_name or "").strip().lower().replace("_", "-")
    if not slug:
        return None
    candidates = []
    for path in (
        f"wp-json/wp/v2/{taxonomy}?search={quote(slug)}&per_page=20",
        f"wp-json/wp/v2/{taxonomy}?search={quote(term_name.strip())}&per_page=20",
        f"wp-json/wp/v2/{taxonomy}?slug={quote(slug)}&per_page=20",
    ):
        try:
            results = rest_request(base_url, user, password, path)
        except Exception:
            continue
        if not isinstance(results, list):
            continue
        candidates.extend(results)
    seen = set()
    for item in candidates:
        if not isinstance(item, dict):
            continue
        item_id = item.get("id")
        item_slug = str(item.get("slug") or "").strip().lower().replace("_", "-")
        item_name = str(item.get("name") or "").strip().lower()
        if item_id is None:
            continue
        if item_slug == slug or item_name == str(term_name).strip().lower():
            if item_id not in seen:
                seen.add(int(item_id))
                return int(item_id)
    return None


def ensure_term_id(base_url: str, user: str, password: str, taxonomy: str, term_name: str) -> Optional[int]:
    term_name = str(term_name or "").strip()
    if not term_name:
        return None
    slug = term_name.lower().replace("_", "-")
    search_path = f"wp-json/wp/v2/{taxonomy}?search={quote(slug)}&per_page=20"
    try:
        results = rest_request(base_url, user, password, search_path)
    except Exception:
        results = []
    if isinstance(results, list):
        for item in results:
            if isinstance(item, dict) and (str(item.get("slug") or "").strip().lower().replace("_", "-") == slug or str(item.get("name") or "").strip().lower() == term_name.lower()):
                return int(item["id"])
    create_payload = {"name": term_name, "slug": slug}
    try:
        created = rest_request(base_url, user, password, f"wp-json/wp/v2/{taxonomy}", method="POST", payload=create_payload)
    except RuntimeError as exc:
        message = str(exc).lower()
        if (
            "401" in message
            or "403" in message
            or "404" in message
            or "rest_cannot_create" in message
            or "cannot_create" in message
            or "rest_no_route" in message
        ):
            return None
        if "term_exists" in message or "already exists" in message:
            existing = find_existing_term_id(base_url, user, password, taxonomy, term_name)
            if existing is not None:
                return existing
            raise
        raise
    if isinstance(created, dict) and "id" in created:
        return int(created["id"])
    return None


def resolve_term_ids(base_url: str, user: str, password: str, taxonomy: str, term_names: Iterable[str]) -> List[int]:
    ids: List[int] = []
    seen = set()
    for name in term_names:
        slug = str(name).strip().lower().replace("_", "-")
        if not slug or slug in seen:
            continue
        seen.add(slug)
        term_id = ensure_term_id(base_url, user, password, taxonomy, name)
        if term_id is not None:
            ids.append(term_id)
    return ids


def prewarm_terms_for_docs(base_url: str, user: str, password: str, docs: Iterable[Dict[str, Any]]) -> None:
    taxonomy = load_taxonomy()
    allowed = set(taxonomy.get("selection_tags", [])) | set(taxonomy.get("descriptive_tags", []))
    tag_names: List[str] = []
    category_names: List[str] = []

    for doc in docs:
        if not isinstance(doc, dict):
            continue
        doc_tags = ensure_taxonomy_terms(doc.get("tags", []))[:5]
        for tag in doc_tags:
            if tag in allowed:
                tag_names.append(tag)

        category = primary_category_for_tags(doc_tags) if doc_tags else "documentation"
        category_names.append(category)

    if tag_names:
        resolve_term_ids(base_url, user, password, "tags", tag_names)
    if category_names:
        resolve_term_ids(base_url, user, password, "categories", category_names)


def normalize_post_status(value: Any, default_status: str = "publish") -> str:
    allowed = {"draft", "pending", "private", "publish"}
    status = str(value or "").strip().lower()
    if status in allowed:
        return status
    if not status:
        return default_status
    return default_status if status in {"publish", "private", "pending"} else "draft"


def build_excerpt(title: str, tags: List[str]) -> str:
    suffix = ", ".join(tags[:3]) if tags else "documentation"
    return f"{title} — {suffix}"


def read_source_text(doc: Dict[str, Any]) -> str:
    source_path = str(doc.get("source_path") or doc.get("relative_path") or "").strip()
    if not source_path:
        return ""
    try:
        return Path(source_path).read_text(encoding="utf-8", errors="replace")
    except Exception:
        return ""


def parse_frontmatter_metadata(text: str) -> Dict[str, Any]:
    if not text or not text.lstrip().startswith("---"):
        return {}

    match = re.match(r"^---\s*(.*?)\s*---\s*(?:\n|$)", text, re.DOTALL)
    if not match:
        match = re.match(r"^---\s*\n(.*?)\n---\s*(?:\n|$)", text, re.DOTALL)
    if not match:
        return {}

    block = match.group(1).strip()
    if not block:
        return {}

    entries: Dict[str, Any] = {}
    for item in re.split(r"(?<!['\"])\s*;\s*(?=[A-Za-z0-9_-]+\s*:)", block):
        if not item.strip():
            continue
        if ':' not in item:
            continue
        key, raw_value = item.split(':', 1)
        key = key.strip().lower()
        value = raw_value.strip()
        if not key:
            continue
        if value.startswith("[") and value.endswith("]"):
            inner = value[1:-1].strip()
            parsed = []
            if inner:
                for part in re.split(r",\s*(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)", inner):
                    part = part.strip().strip("\"'")
                    if part:
                        parsed.append(part)
            entries[key] = parsed
            continue
        if value.lower() in {"true", "false"}:
            entries[key] = value.lower() == "true"
            continue
        if value.lower() in {"null", "none"}:
            entries[key] = None
            continue
        if value.startswith(('"', "'")) and value.endswith(('"', "'")) and len(value) >= 2:
            entries[key] = value[1:-1]
            continue
        try:
            if re.fullmatch(r"-?\d+", value):
                entries[key] = int(value)
                continue
            if re.fullmatch(r"-?\d+\.\d+", value):
                entries[key] = float(value)
                continue
        except ValueError:
            pass
        entries[key] = value
    return entries


def strip_frontmatter(text: str) -> str:
    if not text.startswith("---"):
        return text
    match = re.match(r"^---\s*(.*?)\s*---\s*(?:\n|$)", text, re.DOTALL)
    if not match:
        match = re.match(r"^---\s*\n.*?\n---\s*(?:\n|$)", text, re.DOTALL)
    if not match:
        return text
    return text[match.end():]


def parse_frontmatter(text: str) -> Dict[str, Any]:
    if not text or not text.startswith("---"):
        return {}

    match = re.match(r"^---\s*(.*?)\s*---\s*(?:\n|$)", text, re.DOTALL)
    if not match:
        return {}
    block = match.group(1).strip()
    if not block:
        return {}

    if "\n" not in block and ":" in block:
        items = []
        start = 0
        in_quote = False
        quote_char = ""
        in_brackets = 0
        for idx, char in enumerate(block):
            if char in {'"', "'"}:
                if not in_quote:
                    in_quote = True
                    quote_char = char
                elif char == quote_char:
                    in_quote = False
                    quote_char = ""
            elif not in_quote and char == "[":
                in_brackets += 1
            elif not in_quote and char == "]":
                in_brackets = max(0, in_brackets - 1)
            elif not in_quote and char == ";" and in_brackets == 0:
                items.append(block[start:idx].strip())
                start = idx + 1
        items.append(block[start:].strip())
        data: Dict[str, Any] = {}
        for item in items:
            if not item or ":" not in item:
                continue
            key, raw_value = item.split(":", 1)
            key = key.strip().lower()
            value = raw_value.strip()
            if not value:
                continue
            if key in {"tag", "tags"}:
                parsed_tags: List[str] = []
                if value.startswith("[") and value.endswith("]"):
                    try:
                        parsed = json.loads(value)
                        if isinstance(parsed, list):
                            parsed_tags = [str(item).strip() for item in parsed if str(item).strip()]
                    except Exception:
                        pass
                if not parsed_tags:
                    parsed_tags = [part.strip().strip("\"'") for part in value.split(",") if part.strip()]
                data["tags"] = [str(item).strip() for item in parsed_tags if str(item).strip()]
                continue
            if value.startswith("[") and value.endswith("]"):
                try:
                    parsed = json.loads(value)
                    if isinstance(parsed, list):
                        data[key] = parsed
                        continue
                except Exception:
                    pass
            if value.startswith(("\"", "'")) and value.endswith(("\"", "'")):
                try:
                    value = json.loads(value)
                except Exception:
                    value = value[1:-1]
            if value.lower() in {"true", "false"}:
                data[key] = value.lower() == "true"
                continue
            if value.lower() in {"null", "none"}:
                data[key] = None
                continue
            try:
                if re.fullmatch(r"-?\d+", value):
                    data[key] = int(value)
                    continue
                if re.fullmatch(r"-?\d+\.\d+", value):
                    data[key] = float(value)
                    continue
            except ValueError:
                pass
            data[key] = str(value).strip("\"'")
        return data

    data: Dict[str, Any] = {}
    current_key: Optional[str] = None
    current_list: List[str] = []

    def finalize_list() -> None:
        nonlocal current_key, current_list
        if current_key is not None:
            data[current_key] = current_list
            current_key = None
            current_list = []

    for raw_line in block.splitlines():
        line = raw_line.rstrip()
        if not line.strip() or line.lstrip().startswith("#"):
            continue
        if line.lstrip().startswith("- "):
            if current_key is None:
                continue
            item = line.lstrip()[2:].strip().strip("\"'")
            current_list.append(item)
            continue
        if ":" not in line:
            finalize_list()
            continue
        key, value = line.split(":", 1)
        key = key.strip().lower()
        value = value.strip()
        if value == "":
            finalize_list()
            current_key = key
            current_list = []
            continue
        if key in {"tag", "tags"}:
            parsed_tags: List[str] = []
            if value.startswith("[") and value.endswith("]"):
                inner = value[1:-1].strip()
                if inner:
                    parsed_tags = [part.strip().strip("\"'") for part in inner.split(",") if part.strip()]
            else:
                parsed_tags = [part.strip().strip("\"'") for part in value.split(",") if part.strip()]
            if parsed_tags:
                data["tags"] = [str(item).strip() for item in parsed_tags if str(item).strip()]
            finalize_list()
            continue
        if value.startswith("[") and value.endswith("]"):
            inner = value[1:-1].strip()
            items = []
            if inner:
                items = [part.strip().strip("\"'") for part in inner.split(",")]
                items = [item for item in items if item]
            data[key] = items
            finalize_list()
            continue
        if value.lower() in {"true", "false"}:
            data[key] = value.lower() == "true"
            finalize_list()
            continue
        if value.lower() in {"null", "none"}:
            data[key] = None
            finalize_list()
            continue
        try:
            if re.fullmatch(r"-?\d+", value):
                data[key] = int(value)
                finalize_list()
                continue
            if re.fullmatch(r"-?\d+\.\d+", value):
                data[key] = float(value)
                finalize_list()
                continue
        except ValueError:
            pass
        data[key] = value.strip("\"'")
        finalize_list()
    finalize_list()
    return data


def build_frontmatter_block(metadata: Dict[str, Any]) -> str:
    title = str(metadata.get("title") or "Untitled document").strip()
    slug = metadata.get("slug") or (re.sub(r"[^a-z0-9]+", "-", title.lower()).strip("-") if title else "document")
    entries = [
        ("title", title),
        ("slug", slug),
        ("summary", metadata.get("summary") or ""),
        ("category", metadata.get("category") or "documentation"),
        ("tags", metadata.get("tags") or []),
        ("status", metadata.get("status") or "draft"),
        ("readiness", metadata.get("readiness") or "needs_review"),
        ("score", metadata.get("score") or 0),
        ("author", metadata.get("author") or "ThemisDB"),
        ("coauthors", metadata.get("coauthors") or []),
        ("doi", metadata.get("doi") or ""),
        ("review_status", metadata.get("review_status") or ""),
        ("reviewed", metadata.get("reviewed") if isinstance(metadata.get("reviewed"), bool) else False),
        ("published", metadata.get("published") if isinstance(metadata.get("published"), bool) else False),
        ("language", metadata.get("language") or "und"),
        ("source_origin", metadata.get("source_origin") or "source-links/docs"),
        ("source_path", metadata.get("source_path") or ""),
        ("hash", metadata.get("hash") or ""),
        ("backlog", metadata.get("backlog") or []),
    ]
    parts: List[str] = []
    for key, value in entries:
        if value is None:
            continue
        if key in {"tags", "coauthors", "backlog"}:
            items = [str(item).strip() for item in list(value) if str(item).strip()]
            if items:
                parts.append(f"{key}: [{', '.join(json.dumps(item, ensure_ascii=False) for item in items)}]")
            continue
        if isinstance(value, bool):
            parts.append(f"{key}: {'true' if value else 'false'}")
            continue
        if isinstance(value, (int, float)):
            parts.append(f"{key}: {value}")
            continue
        text = str(value).strip()
        if not text:
            continue
        parts.append(f"{key}: {json.dumps(text, ensure_ascii=False)}")
    compact = "; ".join(parts)
    return f"--- {compact} ---\n"


def extract_markdown_assets(markdown: str) -> List[Dict[str, Any]]:
    text = markdown or ""
    assets: List[Dict[str, Any]] = []
    reference_definitions: Dict[str, str] = {}

    for match in re.finditer(r"(?m)^\s*\[([^\]]+)\]:\s*(\S+)(?:\s+\"([^\"]*)\")?\s*$", text):
        key = match.group(1).strip().lower()
        target = match.group(2).strip()
        if key and target:
            reference_definitions[key] = target

    for match in re.finditer(r"```\s*mermaid\s*\n(.*?)\n```", text, flags=re.IGNORECASE | re.DOTALL):
        payload = match.group(1).strip()
        if payload:
            assets.append({"type": "mermaid", "raw": match.group(0), "target": payload})

    for match in re.finditer(r"!\[[^\]]*\]\(([^)]+)\)", text):
        raw_target = match.group(1).strip()
        cleaned = raw_target.split()[0].strip() if raw_target else ""
        if not cleaned:
            continue
        if cleaned.startswith(("http://", "https://", "data:", "#", "mailto:")):
            if cleaned.startswith("data:"):
                assets.append({"type": "image", "raw": match.group(0), "target": cleaned})
            continue
        assets.append({"type": "image", "raw": match.group(0), "target": cleaned})

    for match in re.finditer(r"!\[(?:[^\]]*)\]\[([^\]]*)\]", text):
        ref_name = match.group(1).strip()
        if not ref_name:
            continue
        target = reference_definitions.get(ref_name.lower()) or reference_definitions.get(ref_name)
        if not target:
            continue
        if target.startswith(("http://", "https://", "data:", "#", "mailto:")):
            if target.startswith("data:"):
                assets.append({"type": "image", "raw": match.group(0), "target": target})
            continue
        assets.append({"type": "image", "raw": match.group(0), "target": target})

    for match in re.finditer(r'<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>', text, flags=re.IGNORECASE | re.DOTALL):
        raw_target = match.group(1).strip()
        if not raw_target or raw_target.startswith(("http://", "https://", "data:", "#", "mailto:")):
            continue
        assets.append({"type": "image", "raw": match.group(0), "target": raw_target})

    return assets


def resolve_local_asset_path(ref: str, current_relative_path: str = "", repo_root: Optional[Path] = None) -> Optional[Path]:
    if not ref:
        return None
    cleaned = ref.split("#", 1)[0].split("?", 1)[0].strip().replace("\\", "/")
    if not cleaned or cleaned.startswith(("http://", "https://", "data:", "mailto:", "#")):
        return None

    repo_root = repo_root or ROOT
    current_dir = repo_root
    if current_relative_path:
        current_file = Path(current_relative_path.replace("\\", "/"))
        current_dir = (repo_root / current_file).parent if current_file.is_absolute() is False else current_file.parent

    candidates = []
    if current_relative_path:
        candidates.append((current_dir / cleaned).resolve())
    candidates.append((repo_root / cleaned).resolve())
    candidates.append((repo_root / cleaned.lstrip("./")).resolve())

    seen = set()
    for candidate in candidates:
        resolved = candidate
        if resolved in seen:
            continue
        seen.add(resolved)
        try:
            if resolved.exists() and resolved.is_file():
                return resolved
        except OSError:
            continue
    return None


def _prepare_wordpress_upload_file(path: Path) -> tuple[Path, str]:
    if not path.exists():
        return path, "application/octet-stream"

    mime_types = {
        ".jpg": "image/jpeg",
        ".jpeg": "image/jpeg",
        ".png": "image/png",
        ".gif": "image/gif",
        ".svg": "image/svg+xml",
        ".webp": "image/webp",
        ".bmp": "image/bmp",
        ".tif": "image/tiff",
        ".tiff": "image/tiff",
        ".pdf": "application/pdf",
        ".doc": "application/msword",
        ".docx": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        ".ppt": "application/vnd.ms-powerpoint",
        ".pptx": "application/vnd.openxmlformats-officedocument.presentationml.presentation",
        ".xls": "application/vnd.ms-excel",
        ".xlsx": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        ".mp3": "audio/mpeg",
        ".wav": "audio/wav",
        ".ogg": "audio/ogg",
        ".m4a": "audio/mp4",
        ".m4b": "audio/mp4",
        ".aac": "audio/aac",
        ".flac": "audio/flac",
        ".opus": "audio/opus",
        ".mp4": "video/mp4",
        ".webm": "video/webm",
        ".mov": "video/quicktime",
        ".m4v": "video/x-m4v",
        ".avi": "video/x-msvideo",
        ".mkv": "video/x-matroska",
        ".wmv": "video/x-ms-wmv",
        ".flv": "video/x-flv",
        ".3gp": "video/3gpp",
    }

    normalized = convert_image_for_wordpress(path)
    mime_type = mime_types.get(normalized.suffix.lower(), mime_types.get(path.suffix.lower(), "application/octet-stream"))
    return normalized, mime_type


def upload_media_asset(base_url: str, user: str, password: str, local_path: str, title: str = "", alt_text: str = "") -> str:
    path = Path(local_path)
    if not path.exists():
        raise FileNotFoundError(f"Asset not found: {local_path}")

    upload_path, mime_type = _prepare_wordpress_upload_file(path)
    upload_name = upload_path.name
    with upload_path.open("rb") as upload_file:
        response = requests.post(
            build_wp_url(base_url, "wp-json/wp/v2/media"),
            auth=(user, password) if user and password else None,
            files={"file": (upload_name, upload_file, mime_type)},
            data={"title": title or path.stem, "alt_text": alt_text or title or path.stem},
            timeout=60,
        )
    if upload_path != path:
        try:
            upload_path.unlink(missing_ok=True)
        except OSError:
            pass
    if response.status_code >= 400:
        raise RuntimeError(f"Media upload failed: {response.status_code}: {response.text[:500]}")
    payload = response.json()
    if isinstance(payload, dict):
        source_url = payload.get("source_url") or payload.get("guid", {}).get("rendered")
        if source_url:
            return str(source_url)
    raise RuntimeError(f"Unexpected media upload response for {upload_name}: {response.text[:500]}")


def upload_media_asset_info(base_url: str, user: str, password: str, local_path: str, title: str = "", alt_text: str = "") -> Optional[Dict[str, Any]]:
    path = Path(local_path)
    if not path.exists():
        return None

    upload_path, mime_type = _prepare_wordpress_upload_file(path)
    upload_name = upload_path.name
    with upload_path.open("rb") as upload_file:
        response = requests.post(
            build_wp_url(base_url, "wp-json/wp/v2/media"),
            auth=(user, password) if user and password else None,
            files={"file": (upload_name, upload_file, mime_type)},
            data={"title": title or path.stem, "alt_text": alt_text or title or path.stem},
            timeout=90,
        )
    if upload_path != path:
        try:
            upload_path.unlink(missing_ok=True)
        except OSError:
            pass
    if response.status_code >= 400:
        return None
    payload = response.json()
    if not isinstance(payload, dict):
        return None
    source_url = payload.get("source_url") or payload.get("guid", {}).get("rendered")
    media_id = payload.get("id")
    if source_url:
        return {"id": media_id, "url": str(source_url)}
    return None


def resolve_article_image_dir(source_path: str) -> Path:
    article_file = Path(source_path)
    direct = article_file.parent / "images" if article_file.parent.exists() else Path(source_path).parent / "images"
    if direct.exists() and any(direct.iterdir()):
        return direct

    page_root = ROOT.parent / "page-content-blog"
    candidate = page_root / "sources" / article_file.parent.name / "images"
    if candidate.exists() and any(candidate.iterdir()):
        return candidate

    normalized_name = normalize_source_lookup_value(article_file.parent.name)
    if normalized_name:
        for src_dir in sorted((page_root / "sources").iterdir(), key=lambda p: p.name.lower()):
            if not src_dir.is_dir():
                continue
            if normalize_source_lookup_value(src_dir.name) == normalized_name:
                image_dir = src_dir / "images"
                if image_dir.exists() and any(image_dir.iterdir()):
                    return image_dir

    if direct.exists() and direct.is_dir():
        return direct
    return candidate if candidate.exists() or candidate.parent.exists() else direct


def resolve_article_media_paths(source_path: str) -> List[Path]:
    article_file = Path(source_path)
    candidates: List[Path] = []
    if article_file.exists() and article_file.parent.exists():
        for path in sorted(article_file.parent.glob("*.*")):
            if path.is_file():
                candidates.append(path)

    image_dir = resolve_article_image_dir(source_path)
    if image_dir.exists():
        for path in sorted(image_dir.glob("*.*")):
            if path.is_file():
                candidates.append(path)

    seen: set[str] = set()
    ordered: List[Path] = []
    for path in candidates:
        key = str(path.resolve())
        if key in seen:
            continue
        seen.add(key)
        ordered.append(path)
    return ordered


def load_image_manifest(doc: Dict[str, Any]) -> Dict[str, Any]:
    source_path = str(doc.get("source_path") or doc.get("relative_path") or "").strip()
    if not source_path:
        return {}
    image_dir = resolve_article_image_dir(source_path)
    manifest_path = image_dir / "manifest.json"
    if not manifest_path.exists():
        return {}
    try:
        payload = json.loads(manifest_path.read_text(encoding="utf-8"))
        return payload if isinstance(payload, dict) else {}
    except Exception:
        return {}


def collect_context_images(doc: Dict[str, Any], title: str) -> List[Dict[str, Any]]:
    source_path = str(doc.get("source_path") or doc.get("relative_path") or "").strip()
    if not source_path:
        return []
    image_dir = resolve_article_image_dir(source_path)
    results: List[Dict[str, Any]] = []
    for idx, path in enumerate(sorted(image_dir.glob("context-*.*")), start=1):
        alt_text = f"Kontextbild {idx} zu {title}"
        results.append({"path": str(path), "alt": alt_text})
    return results


def preflight_import_checks(doc: Dict[str, Any]) -> Dict[str, List[str]]:
    warnings: List[str] = []
    errors: List[str] = []
    source_path = str(doc.get("source_path") or doc.get("relative_path") or "").strip()
    if not source_path:
        return {"warnings": warnings, "errors": errors}

    source_file = Path(source_path)
    image_dir = resolve_article_image_dir(source_path)
    markdown_text = read_document_body(doc)
    image_anchors = re.findall(r"(?m)^\s*<!--\s*IMAGE_ANCHOR:", markdown_text)
    if image_anchors and not image_dir.exists():
        errors.append(f"{len(image_anchors)} IMAGE_ANCHOR markers found but no images folder at {image_dir}")
    featured_candidates = [image_dir / name for name in ("featured.png", "featured.jpg", "featured.jpeg", "featured.webp", "cover.png", "cover.jpg", "hero.png", "hero.jpg")]
    if image_anchors and not any(path.exists() for path in featured_candidates):
        warnings.append("Image markers are present but no featured image was found in the source image folder")
    if image_dir.exists() and not any(image_dir.glob("context-*.*")):
        warnings.append("No context images were found for this article")
    if not source_file.exists() and not image_dir.exists():
        errors.append(f"No source markdown file or images directory could be resolved for {source_path}")
    return {"warnings": warnings, "errors": errors}


def build_blog_media_html(doc: Dict[str, Any], base_url: str, user: str, password: str, dry_run: bool) -> Dict[str, Any]:
    source_path = str(doc.get("source_path") or "").strip()
    if not source_path:
        return {"html": "", "featured_media": None}
    article_file = Path(source_path)
    image_dir = resolve_article_image_dir(source_path)
    article_media_paths = resolve_article_media_paths(source_path)
    if not image_dir.exists() and not article_media_paths:
        return {"html": "", "featured_media": None}

    manifest = load_image_manifest(doc)
    featured_candidates = [image_dir / name for name in ("featured.png", "featured.jpg", "featured.jpeg", "featured.webp", "featured.svg", "cover.png", "cover.jpg", "cover.svg", "hero.png", "hero.jpg", "hero.svg")]
    featured_path = next((path for path in featured_candidates if path.exists()), None)
    audio_feature_path: Optional[Path] = None
    if featured_path is None:
        for path in article_media_paths:
            if path.suffix.lower() in AUDIO_MEDIA_EXTENSIONS:
                audio_feature_path = path
                break
    context_paths = [path for path in sorted(image_dir.glob("context-*.*")) if path.is_file()]
    diagram_paths = [path for path in sorted(image_dir.glob("diagram-*.*")) if path.is_file()]
    if not featured_path and isinstance(manifest, dict):
        for key in ("featured", "hero", "cover"):
            candidate = manifest.get(key)
            if isinstance(candidate, str) and (image_dir / candidate).exists():
                featured_path = image_dir / candidate
                break
    if not featured_path and context_paths:
        featured_path = context_paths[0]
        context_paths = [path for path in context_paths if path != featured_path]
    if not featured_path:
        for path in article_media_paths:
            if path.suffix.lower() in IMAGE_MEDIA_EXTENSIONS:
                featured_path = path
                break
    if featured_path is not None and featured_path.suffix.lower() in IMAGE_MEDIA_EXTENSIONS:
        audio_feature_path = None

    article_dir_media = []
    if article_file.exists() and article_file.parent.exists():
        article_dir_media = [
            path for path in sorted(article_file.parent.glob("*.*"))
            if path.is_file()
            and path.suffix.lower() not in {".json", ".yaml", ".yml", ".mmd", ".md", ".markdown", ".txt"}
            and path not in set(context_paths)
            and path not in set(diagram_paths)
            and path != featured_path
        ]

    media_paths = [
        path for path in sorted(image_dir.glob("*.*"))
        if path.is_file() and path.name.lower() not in {p.name.lower() for p in [featured_path] if p is not None}
        and path.suffix.lower() not in {".json", ".yaml", ".yml", ".mmd", ".md", ".markdown", ".txt"}
        and path not in set(context_paths) and path not in set(diagram_paths)
    ]
    for path in article_dir_media:
        if path not in media_paths:
            media_paths.append(path)

    featured_media_id: Optional[int] = None
    featured_url = ""
    featured_kind = "image"
    context_urls: List[str] = []
    extra_media: List[str] = []

    def upload_media(path: Path) -> tuple[str, Optional[int]]:
        if dry_run:
            return path.as_posix(), None
        uploaded = upload_media_asset_info(base_url, user, password, str(path), path.stem, path.stem)
        if uploaded and uploaded.get("url"):
            media_id = None
            if uploaded.get("id") is not None:
                try:
                    media_id = int(uploaded["id"])
                except Exception:
                    media_id = None
            return str(uploaded["url"]), media_id
        return "", None

    if featured_path:
        featured_url, featured_media_id = upload_media(featured_path)
        featured_kind = "image"
    elif audio_feature_path is not None:
        featured_url, featured_media_id = upload_media(audio_feature_path)
        featured_kind = "audio"

    for path in context_paths:
        url, _ = upload_media(path)
        if url:
            context_urls.append(url)

    for path in diagram_paths:
        url, _ = upload_media(path)
        if url:
            extra_media.append(f'<figure class="themisdb-import-diagram"><img src="{html_lib.escape(url, quote=True)}" alt="Diagram" /></figure>')

    for path in media_paths:
        lower = path.suffix.lower()
        if path == audio_feature_path:
            continue
        if lower in IMAGE_MEDIA_EXTENSIONS:
            url, _ = upload_media(path)
            if url:
                extra_media.append(f'<figure class="themisdb-import-inline-image"><img src="{html_lib.escape(url, quote=True)}" alt="{html_lib.escape(path.stem, quote=True)}" /></figure>')
        elif lower in VIDEO_MEDIA_EXTENSIONS:
            url, _ = upload_media(path)
            if url:
                extra_media.append(f'<figure class="themisdb-import-video"><video class="themisdb-import-video-player" controls preload="metadata" src="{html_lib.escape(url, quote=True)}"></video></figure>')
        elif lower in AUDIO_MEDIA_EXTENSIONS:
            url, _ = upload_media(path)
            if url:
                extra_media.append(f'<figure class="themisdb-import-audio"><audio class="themisdb-import-audio-player" controls src="{html_lib.escape(url, quote=True)}"></audio></figure>')
        elif lower in DOCUMENT_MEDIA_EXTENSIONS:
            url, _ = upload_media(path)
            if url:
                label = html_lib.escape(path.stem)
                extra_media.append(f'<figure class="themisdb-import-document"><a href="{html_lib.escape(url, quote=True)}" target="_blank" rel="noopener">{label}</a></figure>')

    if not featured_url and not context_urls and not extra_media:
        return {"html": "", "featured_media": featured_media_id}

    parts: List[str] = ['<section class="themisdb-import-media">']
    if featured_url:
        if featured_kind == "audio":
            parts.append(f'<figure class="themisdb-import-audio"><audio class="themisdb-import-audio-player" controls preload="metadata" src="{html_lib.escape(featured_url, quote=True)}"></audio></figure>')
        else:
            parts.append(f'<figure class="themisdb-import-media-featured"><img src="{html_lib.escape(featured_url, quote=True)}" alt="Featured image" /></figure>')
    if context_urls:
        parts.append('<div class="themisdb-import-media-context">')
        for url in context_urls:
            parts.append(f'<figure><img src="{html_lib.escape(url, quote=True)}" alt="Context image" /></figure>')
        parts.append('</div>')
    if extra_media:
        parts.extend(extra_media)
    parts.append('</section>')
    return {"html": "".join(parts), "featured_media": featured_media_id}


def resolve_puppeteer_chrome_path() -> Optional[str]:
    env_value = os.environ.get("PUPPETEER_EXECUTABLE_PATH")
    if env_value and Path(env_value).exists():
        return env_value

    executable_names = (
        "google-chrome",
        "google-chrome-stable",
        "chromium",
        "chromium-browser",
        "chrome",
        "chrome-headless-shell",
        "msedge",
    )
    candidates: List[Path] = []
    for exec_name in executable_names:
        resolved = shutil.which(exec_name)
        if resolved:
            candidates.append(Path(resolved))

    for base in (
        Path.home() / ".cache" / "puppeteer",
        Path.home() / ".cache" / "playwright",
        Path("C:/Program Files/Google/Chrome/Application"),
        Path("C:/Program Files (x86)/Google/Chrome/Application"),
        Path("C:/Program Files/Microsoft/Edge/Application"),
        Path("C:/Program Files (x86)/Microsoft/Edge/Application"),
        Path.home() / "AppData" / "Local" / "Google" / "Chrome" / "Application",
        Path.home() / "AppData" / "Local" / "Microsoft" / "Edge" / "Application",
        Path.home() / "AppData" / "Local" / "Chromium" / "Application",
    ):
        if not base.exists():
            continue
        for pattern in (
            "**/chrome.exe",
            "**/chrome-headless-shell.exe",
            "**/msedge.exe",
            "**/chrome",
            "**/google-chrome",
            "**/chromium",
        ):
            candidates.extend(sorted(base.glob(pattern)))

    for candidate in candidates:
        if candidate.exists() and candidate.is_file():
            return str(candidate)

    for chrome_dir in (
        Path.home() / "AppData" / "Local" / "Google" / "Chrome" / "Application",
        Path.home() / "AppData" / "Local" / "Chromium" / "Application",
        Path.home() / ".local" / "share" / "chromium",
        Path.home() / ".local" / "bin",
    ):
        if not chrome_dir.exists():
            continue
        for executable in ("chrome", "google-chrome", "chromium", "chromium-browser"):
            resolved = chrome_dir / executable
            if resolved.exists():
                return str(resolved)
    return None


def render_mermaid_to_image(mermaid_source: str, output_path: Path) -> Path:
    target_path = output_path if output_path.suffix.lower() in {".png", ".jpg", ".jpeg", ".webp"} else output_path.with_suffix(".png")
    target_path.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.NamedTemporaryFile("w", suffix=".mmd", encoding="utf-8", delete=False) as handle:
        handle.write(mermaid_source)
        input_path = Path(handle.name)
    env = os.environ.copy()
    chrome_path = resolve_puppeteer_chrome_path()
    if chrome_path:
        env["PUPPETEER_EXECUTABLE_PATH"] = chrome_path
        env["CHROME_PATH"] = chrome_path

    commands: List[List[str]] = []
    mmdc_cmd = shutil.which("mmdc") or shutil.which("mmdc.cmd")
    if mmdc_cmd:
        commands.append([mmdc_cmd, "-i", str(input_path), "-o", str(target_path), "-b", "transparent"])
    npx_cmd = shutil.which("npx") or shutil.which("npx.cmd") or "npx"
    commands.append([npx_cmd, "--yes", "@mermaid-js/mermaid-cli", "-i", str(input_path), "-o", str(target_path), "-b", "transparent"])

    last_error = "Mermaid render failed"
    try:
        for command in commands:
            result = subprocess.run(command, capture_output=True, text=True, timeout=180, check=False, env=env)
            if result.returncode == 0 and target_path.exists():
                return target_path
            last_error = result.stderr.strip() or result.stdout.strip() or last_error
        raise RuntimeError(last_error)
    finally:
        if input_path.exists():
            input_path.unlink(missing_ok=True)


def render_mermaid_to_svg(mermaid_source: str, output_path: Path) -> Path:
    target_path = output_path if output_path.suffix.lower() == ".svg" else output_path.with_suffix(".svg")
    return render_mermaid_to_image(mermaid_source, target_path)


def convert_image_for_wordpress(image_path: Path) -> Path:
    if image_path.suffix.lower() not in {".png", ".jpg", ".jpeg", ".webp", ".bmp", ".gif", ".tif", ".tiff"}:
        return image_path

    try:
        with Image.open(image_path) as img:
            img = ImageOps.exif_transpose(img)
            if img.mode not in {"RGB", "L"}:
                img = img.convert("RGB")
            jpeg_path = image_path.with_suffix(".jpg")
            img.save(jpeg_path, "JPEG", quality=90, optimize=True)
            return jpeg_path
    except Exception:
        return image_path


def rewrite_markdown_assets(markdown: str, current_relative_path: str = "", base_url: str = "", user: str = "", password: str = "", repo_root: Optional[Path] = None) -> str:
    text = markdown or ""
    if not text or not base_url or not user or not password:
        return text
    repo_root = repo_root or ROOT
    replacements: Dict[str, str] = {}
    for asset in extract_markdown_assets(text):
        raw = asset["raw"]
        asset_type = asset["type"]
        target = asset["target"]
        if asset_type == "mermaid":
            try:
                diagram_path = repo_root / "_tmp" / f"mermaid-{abs(hash(target))}.png"
                diagram_path.parent.mkdir(parents=True, exist_ok=True)
                render_mermaid_to_image(target, diagram_path)
                uploaded_url = upload_media_asset(base_url, user, password, str(diagram_path), "Mermaid diagram", "Mermaid diagram")
                replacement = f'<figure class="themisdb-mermaid"><img src="{uploaded_url}" alt="Mermaid diagram" /></figure>'
            except Exception:
                replacement = f'<pre class="themisdb-mermaid-fallback">{html_lib.escape(target)}</pre>'
        else:
            if target.startswith("data:"):
                alt_text = "Embedded image"
                replacement = f'<img src="{html_lib.escape(target, quote=True)}" alt="{html_lib.escape(alt_text, quote=True)}" />'
            else:
                resolved_path = resolve_local_asset_path(target, current_relative_path, repo_root)
                if resolved_path is None:
                    replacement = raw
                else:
                    try:
                        uploaded_url = upload_media_asset(base_url, user, password, str(resolved_path), resolved_path.name, resolved_path.stem)
                        replacement = f'<img src="{uploaded_url}" alt="{html_lib.escape(resolved_path.stem, quote=True)}" />'
                    except Exception:
                        replacement = raw
        replacements[raw] = replacement

    for raw, replacement in replacements.items():
        text = text.replace(raw, replacement)
    return text


def build_document_link_lookup(documents: Iterable[Dict[str, Any]]) -> Dict[str, str]:
    lookup: Dict[str, str] = {}
    for item in documents:
        if not isinstance(item, dict):
            continue
        rel = str(item.get("relative_path") or item.get("source_path") or "").replace("\\", "/").strip()
        slug = str(item.get("slug") or "").strip()
        if not rel or not slug:
            continue
        rel_norm = rel.lstrip("./")
        lookup[rel_norm.lower()] = slug
        lookup[Path(rel_norm).name.lower()] = slug
        lookup[Path(rel_norm).stem.lower()] = slug
        lookup[slug.lower()] = slug
    return lookup


def resolve_markdown_link_target(target: str, current_relative_path: str = "", doc_lookup: Optional[Dict[str, str]] = None, base_url: str = "") -> str:
    if not target:
        return target

    if target.startswith(("http://", "https://", "mailto:", "#", "data:")):
        if target.startswith(("http://", "https://")) and target.count("(") > target.count(")") and not target.endswith(")"):
            return target + ")"
        return target

    cleaned = target.split("#", 1)[0].split("?", 1)[0].strip()
    if not cleaned:
        return target

    candidate_paths: List[str] = []
    raw_path = cleaned.replace("\\", "/")
    current_dir = str(Path(current_relative_path).parent).replace("\\", "/") if current_relative_path else ""
    if current_dir and current_dir not in {".", "/"}:
        candidate_paths.append((Path(current_dir) / raw_path).as_posix().replace("\\", "/"))
    candidate_paths.extend([
        raw_path,
        raw_path.lstrip("./"),
        raw_path.lstrip("../"),
        Path(raw_path).name,
        Path(raw_path).stem,
        PurePosixPath(raw_path).name if hasattr(PurePosixPath, "name") else raw_path,
    ])

    if doc_lookup:
        seen = set()
        for candidate in candidate_paths:
            normalized = str(candidate).replace("\\", "/").strip()
            for variant in (
                normalized,
                normalized.lower(),
                normalized.lstrip("./"),
                normalized.lstrip("./").lower(),
                Path(normalized).name if normalized else "",
                Path(normalized).name.lower() if normalized else "",
                Path(normalized).stem if normalized else "",
                Path(normalized).stem.lower() if normalized else "",
            ):
                variant = variant.strip()
                if not variant or variant in seen:
                    continue
                seen.add(variant)
                slug = doc_lookup.get(variant.lower())
                if slug:
                    if base_url:
                        return f"{base_url.rstrip('/')}/{slug}"
                    return f"/{slug}"

    return target


def normalize_markdown_to_target_blocks(markdown: str, current_relative_path: str = "", doc_lookup: Optional[Dict[str, str]] = None, base_url: str = "") -> List[Dict[str, Any]]:
    text = strip_frontmatter(markdown or "").strip()
    if not text:
        return []

    text = re.sub(r"(?s)<!--.*?-->", "", text)
    lines = text.splitlines()
    blocks: List[Dict[str, Any]] = []
    i = 0

    def clean_inline(value: str) -> str:
        value = re.sub(r"`([^`]+)`", r"\1", value)
        value = re.sub(r"\*\*([^*]+)\*\*", r"\1", value)
        value = re.sub(r"\*([^*]+)\*", r"\1", value)
        return value.strip()

    while i < len(lines):
        line = lines[i].rstrip()
        if not line.strip():
            i += 1
            continue

        if re.match(r"^```\s*mermaid\s*$", line, flags=re.IGNORECASE):
            i += 1
            content_lines = []
            while i < len(lines) and not lines[i].startswith("```"):
                content_lines.append(lines[i])
                i += 1
            if i < len(lines):
                i += 1
            blocks.append({"type": "mermaid", "code": "\n".join(content_lines).strip(), "caption": None})
            continue

        if line.startswith("```"):
            fence = line[3:].strip() or "text"
            i += 1
            content_lines: List[str] = []
            while i < len(lines) and not lines[i].startswith("```"):
                content_lines.append(lines[i])
                i += 1
            if i < len(lines):
                i += 1
            blocks.append({"type": "code", "language": fence, "code": "\n".join(content_lines)})
            continue

        heading_match = re.match(r"^(#{1,6})\s+(.*)$", line)
        if heading_match:
            level = len(heading_match.group(1))
            text_value = clean_inline(heading_match.group(2).strip())
            blocks.append({"type": "heading", "level": level, "text": text_value})
            i += 1
            continue

        if re.match(r"^>\s*\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*(.*)$", line, flags=re.IGNORECASE):
            variant = re.match(r"^>\s*\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*(.*)$", line, flags=re.IGNORECASE).group(1).lower()
            title = re.match(r"^>\s*\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*(.*)$", line, flags=re.IGNORECASE).group(2).strip()
            body_lines: List[str] = []
            i += 1
            while i < len(lines) and (re.match(r"^>\s+", lines[i]) or re.match(r"^>\s*$", lines[i])):
                content = re.sub(r"^>\s?", "", lines[i]).strip()
                if content:
                    body_lines.append(content)
                i += 1
            blocks.append({
                "type": "callout",
                "variant": variant,
                "title": title or variant.title(),
                "text": " ".join(body_lines).strip(),
            })
            continue

        if re.match(r"^>\s+", line):
            quote_lines = []
            while i < len(lines) and re.match(r"^>\s+", lines[i]):
                quote_lines.append(re.sub(r"^>\s+", "", lines[i]).strip())
                i += 1
            blocks.append({"type": "blockquote", "text": " ".join(quote_lines)})
            continue

        if re.match(r"^[-*+]\s+", line) or re.match(r"^\d+\.\s+", line):
            ordered = bool(re.match(r"^\d+\.\s+", line))
            items = []
            while i < len(lines) and (re.match(r"^[-*+]\s+", lines[i]) or re.match(r"^\d+\.\s+", lines[i])):
                current = lines[i].strip()
                match = re.match(r"^(?:[-*+]|\d+\.)\s+(?:\[(?P<state>[ xX])\]\s*)?(?P<content>.*)$", current)
                if not match:
                    i += 1
                    continue
                content = match.group("content").strip()
                task_state = match.group("state")
                if task_state is not None:
                    items.append({"text": clean_inline(content), "checked": task_state.lower() == "x"})
                elif content:
                    items.append(clean_inline(content))
                i += 1
            blocks.append({"type": "list", "ordered": ordered, "items": [item for item in items if item]})
            continue

        def split_table_row(raw_line: str) -> List[str]:
            text = raw_line.strip()
            if not text:
                return []
            if text.startswith("|"):
                text = text[1:]
            if text.endswith("|"):
                text = text[:-1]
            return [part.strip() for part in text.split("|")]

        def is_table_separator_row(raw_line: str) -> bool:
            cells = split_table_row(raw_line)
            if not cells:
                return False
            return all(re.fullmatch(r":?-{3,}:?", str(cell).strip()) for cell in cells if cell.strip())

        if ("|" in line or "\t" in line) and i + 1 < len(lines):
            table_lines = [line.strip()]
            j = i + 1
            while j < len(lines):
                candidate = lines[j].strip()
                if not candidate or candidate.startswith("```"):
                    break
                if "|" not in candidate and "\t" not in candidate:
                    break
                table_lines.append(candidate)
                j += 1
            if len(table_lines) >= 2:
                rows = []
                for row in table_lines:
                    cells = split_table_row(row)
                    if cells and any(cell.strip() for cell in cells):
                        rows.append(cells)
                if len(rows) >= 2:
                    header = rows[0]
                    candidate_separator = rows[1] if len(rows) > 1 else []
                    if candidate_separator and is_table_separator_row("| " + " | ".join(candidate_separator) + " |"):
                        body_rows = rows[2:]
                        blocks.append({"type": "table", "headers": header, "rows": body_rows})
                        i = j
                        continue

        if line.startswith("<") and ">" in line:
            html_lines = [line]
            i += 1
            while i < len(lines) and ("</" not in lines[i] and "<" in lines[i]):
                html_lines.append(lines[i])
                i += 1
            if i < len(lines) and "</" in lines[i]:
                html_lines.append(lines[i])
                i += 1
            blocks.append({"type": "html", "html": "\n".join(html_lines)})
            continue

        if re.match(r"^---+$", line.strip()):
            blocks.append({"type": "html", "html": "<hr />"})
            i += 1
            continue

        paragraph_lines = []
        while i < len(lines):
            current = lines[i].rstrip()
            if not current.strip():
                break
            if current.startswith("```") or re.match(r"^#{1,6}\s+", current) or re.match(r"^[-*+]\s+", current) or re.match(r"^\d+\.\s+", current) or re.match(r"^>\s+", current) or (current.startswith("<") and ">" in current) or re.match(r"^---+$", current.strip()):
                break
            paragraph_lines.append(current.strip())
            i += 1
        if paragraph_lines:
            blocks.append({"type": "paragraph", "text": " ".join(clean_inline(part) for part in paragraph_lines if part)})
        else:
            i += 1

    return blocks


def validate_target_blocks(blocks: Iterable[Dict[str, Any]]) -> List[str]:
    allowed = {"heading", "paragraph", "list", "table", "image", "mermaid", "code", "blockquote", "callout", "html"}
    issues: List[str] = []
    for index, block in enumerate(blocks, start=1):
        if not isinstance(block, dict):
            issues.append(f"block_{index}: invalid object")
            continue
        block_type = str(block.get("type", "")).strip()
        if block_type not in allowed:
            issues.append(f"block_{index}: unsupported type '{block_type}'")
            continue
        if block_type == "table":
            headers = block.get("headers") or []
            rows = block.get("rows") or []
            if not headers:
                issues.append(f"block_{index}: table missing headers")
            for row_index, row in enumerate(rows, start=1):
                if not isinstance(row, list):
                    issues.append(f"block_{index}: row_{row_index} is not a list")
                    continue
                if headers and len(row) != len(headers):
                    issues.append(f"block_{index}: row_{row_index} width mismatch ({len(row)} != {len(headers)})")
        if block_type == "mermaid":
            code = str(block.get("code") or "").strip()
            if not code:
                issues.append(f"block_{index}: mermaid code is empty")
        if block_type == "code":
            code = str(block.get("code") or "").strip()
            if not code:
                issues.append(f"block_{index}: code block is empty")
        if block_type == "html":
            html = str(block.get("html") or "")
            lowered = html.lower()
            if "<script" in lowered or "<iframe" in lowered or "<style" in lowered:
                issues.append(f"block_{index}: unsafe html block")
    return issues


def lint_markdown_document(markdown: str, current_relative_path: str = "", doc_lookup: Optional[Dict[str, str]] = None, base_url: str = "") -> Dict[str, Any]:
    source = markdown or ""
    stripped = source.strip()
    blocks = normalize_markdown_to_target_blocks(source, current_relative_path=current_relative_path, doc_lookup=doc_lookup, base_url=base_url)
    issues = validate_target_blocks(blocks)
    if not stripped:
        issues.append("empty_markdown")
    if not blocks:
        issues.append("no_renderable_blocks")
    if "<script" in source.lower() or "<iframe" in source.lower() or "<style" in source.lower():
        issues.append("unsafe_raw_html")
    if re.search(r"(?s)<!--.*?-->", source):
        source_without_comments = re.sub(r"(?s)<!--.*?-->", "", source)
        if not source_without_comments.strip():
            issues.append("comment_only_document")

    html = render_target_blocks_to_html(blocks, current_relative_path=current_relative_path, doc_lookup=doc_lookup, base_url=base_url) if blocks else ""
    return {
        "ok": not issues,
        "block_count": len(blocks),
        "block_types": [block.get("type") for block in blocks if isinstance(block, dict)],
        "issues": sorted(set(issues)),
        "html_length": len(html),
    }


def lint_manifest_documents(manifest_path: Path = DOCS_CACHE_PATH, include_low_quality: bool = False, limit: Optional[int] = None) -> Dict[str, Any]:
    manifest = load_json(manifest_path)
    all_docs = manifest.get("documents", [])
    selected = []
    for doc in all_docs:
        if not include_low_quality:
            suitability = doc.get("suitability") if isinstance(doc.get("suitability"), dict) else {}
            readiness = suitability.get("readiness") if isinstance(suitability, dict) else None
            if readiness and readiness != "ready":
                continue
        selected.append(doc)
    if limit is not None:
        selected = selected[:limit]

    results = []
    invalid = 0
    for doc in selected:
        markdown = read_document_body(doc)
        lint = lint_markdown_document(markdown, current_relative_path=str(doc.get("relative_path") or ""), doc_lookup=build_document_link_lookup(all_docs), base_url=DEFAULT_BASE_URL)
        result = {
            "title": doc.get("title"),
            "slug": doc.get("slug"),
            "relative_path": doc.get("relative_path") or doc.get("source_path"),
            "ok": lint["ok"],
            "block_count": lint["block_count"],
            "block_types": lint["block_types"],
            "issues": lint["issues"],
        }
        results.append(result)
        if not lint["ok"]:
            invalid += 1
    return {"source": str(manifest_path), "total": len(results), "valid": len(results) - invalid, "invalid": invalid, "results": results}


def render_target_blocks_to_html(blocks: Iterable[Dict[str, Any]], current_relative_path: str = "", doc_lookup: Optional[Dict[str, str]] = None, base_url: str = "") -> str:
    def escape_html(value: str) -> str:
        return (
            value.replace("&", "&amp;")
            .replace("<", "&lt;")
            .replace(">", "&gt;")
            .replace('"', "&quot;")
            .replace("'", "&#039;")
        )

    def inline_markup(value: str) -> str:
        text = escape_html(value)
        text = re.sub(r"`([^`]+)`", r"<code>\1</code>", text)

        def replace_link(match: re.Match[str]) -> str:
            label = match.group(1)
            target = match.group(2)
            href = resolve_markdown_link_target(target, current_relative_path, doc_lookup, base_url)
            return f'<a href="{escape_html(href)}">{escape_html(label)}</a>'

        text = re.sub(r"\[([^\]]+)\]\(([^)]+)\)", replace_link, text)
        text = re.sub(r"\*\*([^*]+)\*\*", r"<strong>\1</strong>", text)
        text = re.sub(r"\*([^*]+)\*", r"<em>\1</em>", text)
        return text

    rendered: List[str] = []
    mermaid_seen = False
    for block_index, block in enumerate(blocks):
        if not isinstance(block, dict):
            continue
        block_type = str(block.get("type", "")).strip()
        if block_type == "mermaid":
            mermaid_seen = True
        if block_type == "heading":
            level = int(block.get("level") or 1)
            rendered.append(f"<h{level}>{inline_markup(str(block.get('text', '')))}</h{level}>")
        elif block_type == "paragraph":
            previous_types = [
                str(prev.get("type", "")).strip()
                for prev in blocks[:block_index]
                if isinstance(prev, dict)
            ]
            is_lead = bool(block.get("lead")) or (
                block_index > 0 and block_type == "paragraph" and previous_types and all(t == "heading" for t in previous_types)
            )
            lead_attr = ' class="tv3-article-lead"' if is_lead else ""
            rendered.append(f"<p{lead_attr}>{inline_markup(str(block.get('text', '')))}</p>")
        elif block_type == "list":
            ordered = bool(block.get("ordered"))
            tag = "ol" if ordered else "ul"
            items = []
            for item in block.get("items", []) or []:
                if isinstance(item, dict):
                    text = str(item.get("text", "")).strip()
                    checked = bool(item.get("checked"))
                    checkbox = '<input type="checkbox" disabled="disabled" checked="checked" />' if checked else '<input type="checkbox" disabled="disabled" />'
                    items.append(f"<li>{checkbox} {inline_markup(text)}</li>")
                else:
                    items.append(f"<li>{inline_markup(str(item))}</li>")
            rendered.append(f"<{tag}>{''.join(items)}</{tag}>")
        elif block_type == "table":
            headers = block.get("headers", []) or []
            rows = block.get("rows", []) or []
            header_html = "".join(f"<th>{inline_markup(str(cell))}</th>" for cell in headers)
            row_html = []
            for row in rows:
                cells = "".join(f"<td>{inline_markup(str(cell))}</td>" for cell in (row or []))
                row_html.append(f"<tr>{cells}</tr>")
            rendered.append(f"<table><thead><tr>{header_html}</tr></thead><tbody>{''.join(row_html)}</tbody></table>")
        elif block_type == "image":
            src = str(block.get("src", "")).strip()
            alt = str(block.get("alt", "")).strip() or "Image"
            caption = block.get("caption")
            caption_html = f"<figcaption>{inline_markup(str(caption))}</figcaption>" if caption else ""
            rendered.append(f"<figure><img src=\"{escape_html(src)}\" alt=\"{escape_html(alt)}\" />{caption_html}</figure>")
        elif block_type == "mermaid":
            code = str(block.get("code", "")).strip()
            caption = block.get("caption")
            caption_html = f"<figcaption>{inline_markup(str(caption))}</figcaption>" if caption else ""
            rendered.append(
                f"<figure class=\"themisdb-mermaid\"><pre class=\"mermaid\" aria-label=\"Mermaid diagram\">{escape_html(code)}</pre>{caption_html}</figure>"
            )
        elif block_type == "code":
            language = str(block.get("language", "text")).strip()
            code = str(block.get("code", "")).strip()
            rendered.append(f"<pre><code class=\"language-{escape_html(language)}\">{escape_html(code)}</code></pre>")
        elif block_type == "blockquote":
            rendered.append(f"<blockquote><p class=\"quote left kernthese\">{inline_markup(str(block.get('text', '')))}</p></blockquote>")
        elif block_type == "callout":
            variant = str(block.get("variant", "note")).strip() or "note"
            title = str(block.get("title", "")).strip()
            text = str(block.get("text", "")).strip()
            title_html = f"<strong>{escape_html(title)}</strong>" if title else ""
            rendered.append(f"<div class=\"callout callout-{escape_html(variant)}\">{title_html}<p>{inline_markup(text)}</p></div>")
        elif block_type == "html":
            rendered.append(str(block.get("html", "")))

    markup = f'<div class="themisdb-formula-content">{"".join(rendered)}</div>' if rendered else ""
    if mermaid_seen and "mermaid.initialize" not in markup:
        markup += (
            '<script type="module">'
            'import mermaid from "https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs";'
            'mermaid.initialize({ startOnLoad: true, theme: "neutral" });'
            '</script>'
        )
    return markup


def strip_duplicate_leading_h1(html_content: str, title: str) -> str:
    if not html_content or not title:
        return html_content

    def normalize_heading_value(value: str) -> str:
        text = html_lib.unescape(value).replace("&nbsp;", " ")
        text = re.sub(r"<[^>]+>", " ", text)
        text = re.sub(r"[*_`#]+", " ", text)
        text = text.replace("&mdash;", "-").replace("&ndash;", "-").replace("—", "-").replace("–", "-")
        text = text.replace("&quot;", '"').replace("&amp;", "&")
        text = re.sub(r"\s+", " ", text).strip().lower()
        return text

    normalized_title = normalize_heading_value(title)
    if not normalized_title:
        return html_content

    match = re.search(r"(?is)<h1\b[^>]*>(.*?)</h1>", html_content)
    if not match:
        return html_content
    heading_text = normalize_heading_value(match.group(1))
    if heading_text and heading_text == normalized_title:
        return html_content[:match.start()] + html_content[match.end():]
    return html_content


def markdown_to_html(markdown: str, current_relative_path: str = "", doc_lookup: Optional[Dict[str, str]] = None, base_url: str = "") -> str:
    text = strip_frontmatter(markdown or "").strip()
    text = re.sub(r"(?s)<!--.*?-->", "", text)
    if not text:
        return ""
    blocks = normalize_markdown_to_target_blocks(text, current_relative_path=current_relative_path, doc_lookup=doc_lookup, base_url=base_url)
    if blocks:
        issues = validate_target_blocks(blocks)
        if not issues:
            rendered = render_target_blocks_to_html(blocks, current_relative_path=current_relative_path, doc_lookup=doc_lookup, base_url=base_url)
            return strip_duplicate_leading_h1(rendered, "")

    try:
        import markdown as md

        html = md.markdown(
            text,
            extensions=["extra", "sane_lists", "fenced_code", "tables", "attr_list"],
            output_format="html5",
        )
        html = html.strip()
        if html:
            return f'<div class="themisdb-formula-content">{html}</div>'
        return ""
    except Exception:
        rendered = render_target_blocks_to_html(blocks, current_relative_path=current_relative_path, doc_lookup=doc_lookup, base_url=base_url) if blocks else ""
        return strip_duplicate_leading_h1(rendered, "") if rendered else ""


def normalize_iso_datetime(value: Any, fallback: Optional[datetime] = None) -> Optional[str]:
    if value is None:
        if fallback is None:
            return None
        return fallback.astimezone(timezone.utc).isoformat()
    text = str(value).strip()
    if not text:
        if fallback is None:
            return None
        return fallback.astimezone(timezone.utc).isoformat()
    cleaned = text.replace("Z", "+00:00")
    try:
        return datetime.fromisoformat(cleaned).astimezone(timezone.utc).isoformat()
    except ValueError:
        pass
    for fmt in ("%Y-%m-%d %H:%M:%S", "%Y-%m-%d", "%d.%m.%Y", "%d.%m.%Y %H:%M", "%Y/%m/%d", "%Y/%m/%d %H:%M:%S"):
        try:
            dt = datetime.strptime(text, fmt)
            return dt.replace(tzinfo=timezone.utc).isoformat()
        except ValueError:
            continue
    if fallback is not None:
        return fallback.astimezone(timezone.utc).isoformat()
    return None


def resolve_author_user_id(base_url: str, user: str, password: str, author_name: str) -> Optional[int]:
    if not author_name:
        return None
    term = str(author_name).strip()
    if not term:
        return None
    for path in (f"wp-json/wp/v2/users?search={quote(term)}&per_page=10", "wp-json/wp/v2/users?per_page=100"):
        try:
            users = rest_request(base_url, user, password, path)
        except Exception:
            continue
        if not isinstance(users, list):
            continue
        for entry in users:
            if not isinstance(entry, dict):
                continue
            name = str(entry.get("name") or "").strip()
            login = str(entry.get("slug") or entry.get("username") or "").strip()
            if name.lower() == term.lower() or login.lower() == term.lower().replace(" ", "-"):
                return int(entry["id"])
    return None


_CURRENT_USER_ID_CACHE: Dict[str, Optional[int]] = {}


def resolve_current_user_id(base_url: str, user: str, password: str) -> Optional[int]:
    cache_key = f"{base_url}|{user}"
    if cache_key in _CURRENT_USER_ID_CACHE:
        return _CURRENT_USER_ID_CACHE[cache_key]
    try:
        current_user = rest_request(base_url, user, password, "wp-json/wp/v2/users/me")
    except Exception:
        _CURRENT_USER_ID_CACHE[cache_key] = None
        return None
    if isinstance(current_user, dict) and current_user.get("id") is not None:
        try:
            resolved = int(current_user["id"])
            _CURRENT_USER_ID_CACHE[cache_key] = resolved
            return resolved
        except Exception:
            _CURRENT_USER_ID_CACHE[cache_key] = None
            return None
    _CURRENT_USER_ID_CACHE[cache_key] = None
    return None


def extract_doc_metadata(doc: Dict[str, Any]) -> Dict[str, Any]:
    metadata = doc.get("metadata") if isinstance(doc.get("metadata"), dict) else {}
    author = metadata.get("author") or "ThemisDB"
    coauthors = metadata.get("coauthors") or metadata.get("contributors") or []
    if isinstance(coauthors, str):
        coauthors = [name.strip() for name in coauthors.split(",") if name.strip()]
    coauthors = [str(name).strip() for name in list(dict.fromkeys(coauthors)) if str(name).strip()]
    if not coauthors and "gemini" in str(doc.get("source_path", "")).lower():
        coauthors = ["Gemini"]
    if not coauthors and "swarm" in str(doc.get("source_path", "")).lower():
        coauthors = ["SwarmUI"]
    date_value = metadata.get("date") or metadata.get("published_at") or metadata.get("created_at") or doc.get("last_modified")
    created_value = metadata.get("created_at") or doc.get("last_modified")
    modified_value = metadata.get("modified_at") or doc.get("last_modified")
    published_value = metadata.get("published_at") or date_value
    document_hash = metadata.get("sha256") or metadata.get("hash") or doc.get("hash") or doc.get("sha256")
    return {
        "author": str(author).strip() or "ThemisDB",
        "coauthors": coauthors,
        "date": normalize_iso_datetime(date_value),
        "created_at": normalize_iso_datetime(created_value),
        "modified_at": normalize_iso_datetime(modified_value),
        "published_at": normalize_iso_datetime(published_value),
        "source_path": doc.get("source_path", ""),
        "source_origin": metadata.get("source_origin") or "source-links/docs",
        "language": metadata.get("language") or "und",
        "related_docs": metadata.get("related_docs") or [],
        "relation_graph": metadata.get("relation_graph") or {"nodes": [], "edges": []},
        "sha256": document_hash,
        "status": metadata.get("status") or doc.get("status") or "draft",
        "category": metadata.get("category") or doc.get("category") or "",
        "summary": metadata.get("summary") or doc.get("excerpt") or "",
    }


def resolve_document_source_path(doc: Dict[str, Any]) -> Optional[Path]:
    candidates: List[Path] = []
    for key in ("source_path", "relative_path"):
        value = doc.get(key)
        if value:
            candidates.append(Path(str(value)))

    if not candidates:
        return None

    seen = set()
    for candidate in candidates:
        if not candidate.exists():
            continue
        key = str(candidate.resolve())
        if key in seen:
            continue
        seen.add(key)
        if candidate.suffix.lower() in {".md", ".markdown", ".txt"}:
            return candidate

    for candidate in candidates:
        if candidate.parent.exists():
            for suffix in (".md", ".markdown", ".txt"):
                sibling = candidate.with_suffix(suffix)
                if sibling.exists():
                    return sibling
            article_md = candidate.parent / "article.md"
            if article_md.exists():
                return article_md
            for child in sorted(candidate.parent.glob("*.md")):
                if child.is_file() and not child.name.endswith((".duplicate", ".bak", ".bak2")):
                    return child

    for candidate in candidates:
        folder = candidate.parent
        for path in sorted(folder.rglob("*.md")):
            if path.is_file() and not path.name.endswith((".duplicate", ".bak", ".bak2")):
                return path

    return None


def read_document_body(doc: Dict[str, Any]) -> str:
    source_path = resolve_document_source_path(doc)
    if source_path is not None:
        try:
            return source_path.read_text(encoding="utf-8", errors="replace")
        except Exception:
            pass

    source_lookup = str(doc.get("source_path") or doc.get("relative_path") or "").strip()
    if source_lookup:
        try:
            path = Path(source_lookup)
            if path.exists() and path.is_file():
                return path.read_text(encoding="utf-8", errors="replace")
        except Exception:
            pass

    output_lookup = str(doc.get("output_path") or "").strip()
    if output_lookup:
        try:
            output_path = Path(output_lookup)
            if output_path.exists() and output_path.is_file():
                return output_path.read_text(encoding="utf-8", errors="replace")
        except Exception:
            pass
    return ""


def coerce_term_list(value: Any) -> List[str]:
    if value is None:
        return []
    if isinstance(value, (list, tuple, set)):
        values: List[str] = []
        for item in value:
            values.extend(coerce_term_list(item))
        return values
    if isinstance(value, dict):
        return [str(v).strip() for key, v in value.items() if str(v).strip()]
    text = str(value).strip()
    if not text:
        return []
    if "," in text or ";" in text or "\n" in text:
        parts = [part.strip() for part in text.replace(";", ",").split("\n")]
        values = []
        for part in parts:
            for sub in part.split(","):
                candidate = sub.strip()
                if candidate:
                    values.append(candidate)
        return values
    return [text]


def extract_custom_taxonomy_values(doc: Dict[str, Any]) -> Dict[str, List[str]]:
    custom_taxonomies: Dict[str, List[str]] = {}
    containers = [doc]
    metadata = doc.get("metadata") if isinstance(doc.get("metadata"), dict) else {}
    if metadata:
        containers.append(metadata)
    if isinstance(doc.get("taxonomy"), dict):
        containers.append(doc["taxonomy"])
    if isinstance(doc.get("custom_taxonomies"), dict):
        containers.append(doc["custom_taxonomies"])

    for container in containers:
        if not isinstance(container, dict):
            continue
        for key, value in container.items():
            key_name = str(key).strip().lower().replace("-", "_").replace(" ", "_")
            taxonomy = {
                "feature": "themisdb_feature",
                "features": "themisdb_feature",
                "use_case": "themisdb_usecase",
                "use_cases": "themisdb_usecase",
                "industry": "themisdb_industry",
                "industries": "themisdb_industry",
                "techspec": "themisdb_techspec",
                "techspecs": "themisdb_techspec",
                "technical_spec": "themisdb_techspec",
                "technical_specs": "themisdb_techspec",
                "themisdb_feature": "themisdb_feature",
                "themisdb_usecase": "themisdb_usecase",
                "themisdb_industry": "themisdb_industry",
                "themisdb_techspec": "themisdb_techspec",
            }.get(key_name)
            if taxonomy is None:
                continue
            values = coerce_term_list(value)
            clean_values = []
            seen = set()
            for item in values:
                item = str(item).strip()
                if not item:
                    continue
                normalized = item.lower().replace("_", "-")
                if normalized in seen:
                    continue
                seen.add(normalized)
                clean_values.append(item)
            if clean_values:
                custom_taxonomies.setdefault(taxonomy, [])
                custom_taxonomies[taxonomy].extend(clean_values)

    deduped = {}
    for taxonomy, values in custom_taxonomies.items():
        unique = []
        seen = set()
        for value in values:
            normalized = str(value).strip().lower().replace("_", "-")
            if not normalized or normalized in seen:
                continue
            seen.add(normalized)
            unique.append(str(value).strip())
        if unique:
            deduped[taxonomy] = unique
    return deduped


def normalize_coauthor_names(values: Iterable[str]) -> List[str]:
    aliases = {
        "google gemini": "Google Gemini",
        "google-gemini": "Google Gemini",
        "gemini": "Google Gemini",
        "github": "GitHub Copilot",
        "github copilot": "GitHub Copilot",
        "github-copilot": "GitHub Copilot",
        "copilot": "GitHub Copilot",
        "gemma4": "Gemma4 (Lektor)",
        "gemma4 lektor": "Gemma4 (Lektor)",
        "gemma4-lektor": "Gemma4 (Lektor)",
        "swarmui": "SwarmUI (Grafiker)",
        "swarmui grafiker": "SwarmUI (Grafiker)",
        "swarmui-grafiker": "SwarmUI (Grafiker)",
        "anthropic": "Anthropic Claude",
        "anthropic claude": "Anthropic Claude",
        "anthropic-claude": "Anthropic Claude",
        "claude": "Anthropic Claude",
        "openai": "OpenAI GPT-4o",
        "openai gpt-4o": "OpenAI GPT-4o",
        "openai-gpt-4o": "OpenAI GPT-4o",
        "gpt-4o": "OpenAI GPT-4o",
        "gpt4o": "OpenAI GPT-4o",
        "meta llama": "Meta Llama 3",
        "meta llama 3": "Meta Llama 3",
        "meta-llama": "Meta Llama 3",
        "meta-llama-3": "Meta Llama 3",
        "llama": "Meta Llama 3",
        "llama 3": "Meta Llama 3",
        "llama3": "Meta Llama 3",
        "mistral": "Mistral Large",
        "mistral large": "Mistral Large",
        "mistral-large": "Mistral Large",
        "deepseek": "DeepSeek R1",
        "deepseek r1": "DeepSeek R1",
        "deepseek-r1": "DeepSeek R1",
        "dall-e": "OpenAI DALL-E",
        "dall e": "OpenAI DALL-E",
        "openai dall-e": "OpenAI DALL-E",
        "openai-dall-e": "OpenAI DALL-E",
        "stable diffusion": "Stable Diffusion",
        "stable-diffusion": "Stable Diffusion",
        "midjourney": "Midjourney",
        "black forest labs flux": "Black Forest Labs Flux",
        "blackforest labs flux": "Black Forest Labs Flux",
        "blackforest-flux": "Black Forest Labs Flux",
        "flux": "Black Forest Labs Flux",
    }

    normalized: List[str] = []
    seen = set()
    for value in values:
        name = str(value).strip()
        if not name:
            continue
        canonical = aliases.get(name.lower()) or aliases.get(name.lower().replace("_", " ")) or name.strip()
        key = canonical.lower()
        if key in seen:
            continue
        seen.add(key)
        normalized.append(canonical)
    return normalized


def guest_author_slug_for_name(name: str) -> str:
    if not name:
        return ""
    map_by_name = {
        "Google Gemini": "google-gemini",
        "Gemini": "google-gemini",
        "GitHub": "github-copilot",
        "GitHub Copilot": "github-copilot",
        "Gemma4 (Lektor)": "gemma4-lektor",
        "SwarmUI (Grafiker)": "swarmui-grafiker",
        "Anthropic Claude": "anthropic-claude",
        "Claude": "anthropic-claude",
        "OpenAI GPT-4o": "openai-gpt-4o",
        "GPT-4o": "openai-gpt-4o",
        "Meta Llama 3": "meta-llama-3",
        "Llama": "meta-llama-3",
        "Mistral Large": "mistral-large",
        "Mistral": "mistral-large",
        "DeepSeek R1": "deepseek-r1",
        "DeepSeek": "deepseek-r1",
        "OpenAI DALL-E": "openai-dall-e",
        "DALL-E": "openai-dall-e",
        "Stable Diffusion": "stable-diffusion",
        "Midjourney": "midjourney",
        "Black Forest Labs Flux": "blackforest-flux",
        "Flux": "blackforest-flux",
    }
    candidate = str(name).strip()
    if candidate in map_by_name:
        return map_by_name[candidate]
    key = candidate.lower()
    for canonical, slug in map_by_name.items():
        if canonical.lower() == key or slug.lower() == key or slug.replace("cap-", "").lower() == key.replace(" ", "-"):
            return slug
    return ""


def resolve_guest_author_ids(base_url: str, user: str, password: str, coauthor_names: Iterable[str]) -> List[int]:
    ids: List[int] = []
    seen = set()
    for name in coauthor_names:
        candidate = guest_author_slug_for_name(str(name))
        if not candidate:
            continue
        if candidate in seen:
            continue
        seen.add(candidate)
        try:
            results = rest_request(base_url, user, password, f"wp-json/wp/v2/guest-author?slug={quote(candidate)}&per_page=10")
        except Exception:
            continue
        if not isinstance(results, list):
            continue
        for item in results:
            if not isinstance(item, dict):
                continue
            if str(item.get("slug") or "").strip() == candidate:
                item_id = item.get("id")
                if item_id is not None:
                    ids.append(int(item_id))
                    break
    return ids


def resolve_coauthor_assignment_values(values: Iterable[str]) -> List[str]:
    normalized = normalize_coauthor_names(values or [])
    resolved: List[str] = []
    seen = set()
    for name in normalized:
        slug = guest_author_slug_for_name(name) or name.strip()
        key = slug.lower()
        if not slug or key in seen:
            continue
        seen.add(key)
        resolved.append(slug)
    return resolved


def assign_post_coauthors(base_url: str, user: str, password: str, post_id: Any, coauthor_names: Iterable[str]) -> Optional[Dict[str, Any]]:
    assigned = resolve_coauthor_assignment_values(coauthor_names or [])
    if not post_id or not assigned:
        return None
    try:
        return rest_request(
            base_url,
            user,
            password,
            f"wp-json/coauthors/v1/authors/{post_id}",
            method="POST",
            payload={"new_authors": ", ".join(assigned)},
        )
    except Exception:
        return None


def discover_source_documents(manifest_path: Path = DOCS_CACHE_PATH, blog_sources_root: Optional[Path] = None) -> List[Dict[str, Any]]:
    if blog_sources_root is not None:
        return build_blog_documents_from_sources(blog_sources_root)
    return load_json(Path(manifest_path)).get("documents", [])


def resolve_document_metadata(doc: Dict[str, Any]) -> Dict[str, Any]:
    metadata = extract_doc_metadata(doc)
    title = str(doc.get("title") or metadata.get("title") or "Untitled document").strip() or "Untitled document"
    slug = str(doc.get("slug") or metadata.get("slug") or slugify_text(title)).strip() or slugify_text(title)
    status = normalize_post_status(metadata.get("status"), default_status="publish")
    source_path = str(doc.get("source_path") or metadata.get("source_path") or doc.get("relative_path") or "").strip()
    raw_excerpt = str((doc.get("excerpt") or metadata.get("summary") or "")).strip()
    return {
        "title": title,
        "slug": slug,
        "status": status,
        "source_path": source_path,
        "source_origin": str(metadata.get("source_origin") or doc.get("source_origin") or "page-content-blog/sources").strip(),
        "date": str(metadata.get("date") or metadata.get("published_at") or metadata.get("created_at") or "").strip(),
        "published_at": str(metadata.get("published_at") or metadata.get("date") or metadata.get("created_at") or "").strip(),
        "created_at": str(metadata.get("created_at") or doc.get("last_modified") or "").strip(),
        "modified_at": str(metadata.get("modified_at") or doc.get("last_modified") or metadata.get("created_at") or "").strip(),
        "author": str(metadata.get("author") or "ThemisDB").strip(),
        "coauthors": list(metadata.get("coauthors") or []),
        "excerpt": raw_excerpt,
        "category": str(metadata.get("category") or doc.get("category") or "").strip(),
    }


def apply_source_tags_and_categories(doc: Dict[str, Any], metadata: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
    resolved = metadata or resolve_document_metadata(doc)
    heuristic_tags = source_match_tags_for_path(doc.get("source_path"), str(doc.get("title") or ""))
    explicit_tags = merge_tag_list(doc.get("tags", []), heuristic_tags)
    if doc.get("allow_unrestricted_tags"):
        tags = explicit_tags[:12]
    else:
        tags = ensure_taxonomy_terms(explicit_tags)[:12]
    category_names = resolve_document_category_names(doc, tags, str(resolved.get("category") or "").strip())
    if not category_names:
        category_names = ["documentation"]
    return {"tags": list(dict.fromkeys(tags)), "categories": list(dict.fromkeys(category_names))}


def collect_article_media(doc: Dict[str, Any]) -> Dict[str, Any]:
    source_path = str(doc.get("source_path") or doc.get("relative_path") or "").strip()
    if not source_path:
        return {"featured": None, "context": [], "diagram": [], "audio": [], "video": [], "images": [], "all_media": []}

    article_file = Path(source_path)
    image_dir = resolve_article_image_dir(source_path)
    featured_candidates = [
        image_dir / name for name in (
            "featured.png",
            "featured.jpg",
            "featured.jpeg",
            "featured.webp",
            "cover.png",
            "cover.jpg",
            "hero.png",
            "hero.jpg",
        )
    ]
    featured = next((path for path in featured_candidates if path.exists()), None)
    context_paths = [path for path in sorted(image_dir.glob("context-*.*")) if path.is_file()]
    diagram_paths = [path for path in sorted(image_dir.glob("diagram-*.*")) if path.is_file()]
    article_dir_files = []
    if article_file.exists() and article_file.parent.exists():
        article_dir_files = [
            path for path in sorted(article_file.parent.glob("*.*"))
            if path.is_file()
            and path.suffix.lower() not in {".json", ".yaml", ".yml", ".mmd", ".md", ".markdown", ".txt"}
        ]
    media_paths = [
        path for path in sorted(image_dir.glob("*.*"))
        if path.is_file() and path.suffix.lower() not in {".json", ".yaml", ".yml", ".mmd", ".md", ".markdown", ".txt"}
    ]
    for path in article_dir_files:
        if path not in media_paths:
            media_paths.append(path)

    images = [path for path in media_paths if path.suffix.lower() in {".png", ".jpg", ".jpeg", ".webp", ".gif", ".bmp", ".tif", ".tiff"}]
    audio = [path for path in media_paths if path.suffix.lower() in {".mp3", ".wav", ".ogg", ".m4a", ".aac", ".flac"}]
    video = [path for path in media_paths if path.suffix.lower() in {".mp4", ".webm", ".mov", ".m4v", ".avi", ".mkv"}]
    return {
        "featured": featured,
        "context": context_paths,
        "diagram": diagram_paths,
        "audio": audio,
        "video": video,
        "images": images,
        "all_media": media_paths,
    }


def upload_media_bundle(doc: Dict[str, Any], base_url: str = "", user: str = "", password: str = "", dry_run: bool = False) -> Dict[str, Any]:
    media = collect_article_media(doc)
    if not base_url or not user or not password or dry_run:
        return {"featured_media": None, "html": "", "media": media}
    html_block = build_blog_media_html(doc, base_url, user, password, dry_run=False)
    return {"featured_media": html_block.get("featured_media"), "html": html_block.get("html", ""), "media": media}


def render_document_content(doc: Dict[str, Any], base_url: str = "", user: str = "", password: str = "", dry_run: bool = False, link_lookup: Optional[Dict[str, str]] = None) -> str:
    metadata = resolve_document_metadata(doc)
    source_markdown = read_document_body(doc)
    if not source_markdown:
        return ""
    source_markdown_for_html = rewrite_markdown_assets(
        source_markdown,
        current_relative_path=str(doc.get("relative_path") or ""),
        base_url=base_url,
        user=user,
        password=password,
        repo_root=ROOT.parent,
    ) if source_markdown and not dry_run else source_markdown
    source_html = markdown_to_html(source_markdown_for_html, current_relative_path=str(doc.get("relative_path") or ""), doc_lookup=link_lookup, base_url=base_url) if source_markdown_for_html else ""
    source_html = strip_duplicate_leading_h1(source_html, str(doc.get("title") or metadata.get("title") or ""))
    media_block = build_blog_media_html(doc, base_url, user, password, dry_run=dry_run)
    media_html = str(media_block.get("html") or "").strip()
    if media_html:
        return media_html + "\n" + source_html
    return source_html


def upsert_wordpress_post(base_url: str, user: str, password: str, doc: Dict[str, Any], dry_run: bool = False, link_lookup: Optional[Dict[str, str]] = None, update_only: bool = False) -> Dict[str, Any]:
    return create_or_update_document(
        base_url,
        user,
        password,
        doc,
        dry_run=dry_run,
        link_lookup=link_lookup,
        update_only=update_only,
    )


def persist_import_state(doc: Dict[str, Any], post_result: Dict[str, Any], media_result: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
    state_path = LEGACY_IMPORT_STATE_PATH
    state: Dict[str, Any] = {}
    if state_path.exists():
        try:
            parsed = json.loads(state_path.read_text(encoding="utf-8"))
            if isinstance(parsed, dict):
                state = parsed
        except Exception:
            state = {}
    documents_state = state.setdefault("documents", {})
    source_key = str(doc.get("source_path") or doc.get("relative_path") or doc.get("slug") or doc.get("title") or "unknown")
    documents_state[source_key] = {
        "title": doc.get("title"),
        "slug": doc.get("slug"),
        "source_path": str(doc.get("source_path") or doc.get("relative_path") or ""),
        "wp_action": post_result.get("action"),
        "updated_at": datetime.now(timezone.utc).isoformat(),
        "featured_media": (media_result or {}).get("featured_media"),
    }
    tmp_path = state_path.with_suffix(".json.tmp")
    tmp_path.write_text(json.dumps(make_json_safe(state), ensure_ascii=False, indent=2), encoding="utf-8")
    tmp_path.replace(state_path)
    return state


def normalize_post_payload(doc: Dict[str, Any], base_url: str = "", user: str = "", password: str = "", dry_run: bool = False, link_lookup: Optional[Dict[str, str]] = None) -> Dict[str, Any]:
    taxonomy = load_taxonomy()
    unrestricted_tags = bool(doc.get("allow_unrestricted_tags"))
    heuristic_tags = source_match_tags_for_path(doc.get("source_path"), str(doc.get("title") or ""))
    if unrestricted_tags:
        raw_tags = merge_tag_list(doc.get("tags", []), heuristic_tags)[:12]
    else:
        raw_tags = ensure_taxonomy_terms(merge_tag_list(doc.get("tags", []), heuristic_tags))[:12]
    tags = list(dict.fromkeys(raw_tags))
    public_selection = [tag for tag in tags if tag in taxonomy.get("selection_tags", [])]
    descriptive = [tag for tag in tags if tag in taxonomy.get("descriptive_tags", [])]
    metadata = extract_doc_metadata(doc)
    explicit_category = str(metadata.get("category") or "").strip()
    category_names = resolve_document_category_names(doc, tags, explicit_category)
    if not public_selection and descriptive:
        category_names = [descriptive[0]]
    custom_taxonomies = extract_custom_taxonomy_values(doc)
    author_id = resolve_author_user_id(base_url, user, password, metadata["author"]) if base_url and user and password else None
    current_user_id = resolve_current_user_id(base_url, user, password) if base_url and user and password else None
    payload_author: Optional[int] = None
    if author_id is not None and current_user_id is not None and author_id == current_user_id:
        payload_author = author_id
    elif author_id is not None and current_user_id is None:
        payload_author = author_id
    elif current_user_id is not None:
        payload_author = current_user_id
    source_markdown = read_document_body(doc)
    source_markdown_for_html = (
        rewrite_markdown_assets(
            source_markdown,
            current_relative_path=str(doc.get("relative_path") or ""),
            base_url=base_url,
            user=user,
            password=password,
            repo_root=ROOT.parent,
        )
        if source_markdown and not dry_run
        else (source_markdown or "")
    )
    source_html = markdown_to_html(source_markdown_for_html, current_relative_path=str(doc.get("relative_path") or ""), doc_lookup=link_lookup, base_url=base_url) if source_markdown_for_html else ""
    source_html = strip_duplicate_leading_h1(source_html, str(doc.get("title") or ""))
    coauthor_names = normalize_coauthor_names(metadata.get("coauthors") or [])
    if not coauthor_names and metadata.get("author"):
        coauthor_names = normalize_coauthor_names([metadata["author"]])
    coauthor_csv = ", ".join(coauthor_names)
    coauthor_ids = resolve_guest_author_ids(base_url, user, password, coauthor_names) if base_url and user and password else []
    content_body = source_html or f"<h2>{doc.get('title', 'Document')}</h2><p>Source: {doc.get('relative_path', '')}</p>"

    featured_media_id: Optional[int] = None
    if str(doc.get("pipeline") or "").strip().lower() == "page-content-blog":
        media_block = build_blog_media_html(doc, base_url, user, password, dry_run=dry_run)
        media_html = str(media_block.get("html") or "").strip()
        if media_html:
            content_body = media_html + "\n" + content_body
        media_id = media_block.get("featured_media")
        if isinstance(media_id, int):
            featured_media_id = media_id

    payload = {
        "title": doc.get("title", "Untitled document"),
        "slug": doc.get("slug", "document"),
        "status": normalize_post_status(metadata.get("status"), default_status="publish"),
        "content": content_body,
        "excerpt": str(doc.get("excerpt") or metadata.get("summary") or build_excerpt(doc.get("title", "Untitled document"), tags)),
        "date": metadata["date"] or metadata["published_at"] or metadata["created_at"],
        "date_gmt": (metadata["date"] or metadata["published_at"] or metadata["created_at"]).replace("+00:00", "+00:00") if (metadata["date"] or metadata["published_at"] or metadata["created_at"]) else None,
        "modified": metadata["modified_at"] or metadata["date"] or metadata["created_at"],
        "modified_gmt": (metadata["modified_at"] or metadata["date"] or metadata["created_at"]).replace("+00:00", "+00:00") if (metadata["modified_at"] or metadata["date"] or metadata["created_at"]) else None,
        "tags": tags,
        "categories": category_names,
        "meta_input": {
            "themisdb_author": metadata["author"],
            "themisdb_coauthors": coauthor_csv,
            "tv3_ai_coauthors": coauthor_csv,
            "themisdb_ai_coauthors": coauthor_csv,
            "coauthors": coauthor_csv,
            "themisdb_date": metadata["date"] or metadata["published_at"] or metadata["created_at"] or "",
            "themisdb_created_at": metadata["created_at"] or "",
            "themisdb_modified_at": metadata["modified_at"] or "",
            "themisdb_published_at": metadata["published_at"] or "",
            "themisdb_language": metadata["language"],
            "themisdb_source_path": metadata["source_path"],
            "themisdb_source_origin": metadata["source_origin"],
            "themisdb_document_hash": metadata.get("sha256") or doc.get("hash") or hashlib.sha256((source_markdown or "").encode("utf-8")).hexdigest(),
            "themisdb_raw_markdown": source_markdown[:20000],
            "themisdb_related_docs": json.dumps(metadata["related_docs"], ensure_ascii=False),
            "themisdb_relation_graph": json.dumps(metadata["relation_graph"], ensure_ascii=False),
            "themisdb_custom_taxonomies": json.dumps(custom_taxonomies, ensure_ascii=False),
            "themisdb_document_metadata": json.dumps({
                "author": metadata["author"],
                "coauthors": metadata["coauthors"],
                "date": metadata["date"],
                "created_at": metadata["created_at"],
                "modified_at": metadata["modified_at"],
                "published_at": metadata["published_at"],
                "language": metadata["language"],
                "source_origin": metadata["source_origin"],
                "source_path": metadata["source_path"],
                "related_docs": metadata["related_docs"],
                "relation_graph": metadata["relation_graph"],
                "custom_taxonomies": custom_taxonomies,
                "sha256": metadata.get("sha256") or doc.get("hash") or hashlib.sha256((source_markdown or "").encode("utf-8")).hexdigest(),
            }, ensure_ascii=False),
        },
    }
    if featured_media_id is not None:
        payload["featured_media"] = featured_media_id
    if payload_author is not None:
        payload["author"] = payload_author
    if custom_taxonomies:
        payload["tax_input"] = {taxonomy_name: list(values) for taxonomy_name, values in custom_taxonomies.items()}
    if dry_run:
        return payload

    payload["tags"] = resolve_term_ids(base_url, user, password, "tags", tags)
    payload["categories"] = resolve_term_ids(base_url, user, password, "categories", category_names)
    if payload_author is not None:
        payload["author"] = payload_author
    else:
        payload.pop("author", None)
    payload["tax_input"] = {
        taxonomy_name: resolve_term_ids(base_url, user, password, taxonomy_name, values)
        for taxonomy_name, values in custom_taxonomies.items()
    }
    payload["meta_input"]["themisdb_document_metadata"] = json.dumps({
        "author": metadata["author"],
        "coauthors": metadata["coauthors"],
        "date": metadata["date"],
        "created_at": metadata["created_at"],
        "modified_at": metadata["modified_at"],
        "published_at": metadata["published_at"],
        "language": metadata["language"],
        "source_origin": metadata["source_origin"],
        "source_path": metadata["source_path"],
        "related_docs": metadata["related_docs"],
        "relation_graph": metadata["relation_graph"],
        "custom_taxonomies": custom_taxonomies,
        "sha256": metadata.get("sha256") or doc.get("hash") or hashlib.sha256((source_markdown or "").encode("utf-8")).hexdigest(),
    }, ensure_ascii=False)
    return payload


def create_or_update_document(
    base_url: str,
    user: str,
    password: str,
    doc: Dict[str, Any],
    dry_run: bool = False,
    link_lookup: Optional[Dict[str, str]] = None,
    update_only: bool = False,
) -> Dict[str, Any]:
    should_probe_existing = (not dry_run) or update_only
    existing = (
        existing_wp_doc(base_url, user, password, doc.get("source_path", ""), doc.get("slug", ""), doc.get("title", ""))
        if should_probe_existing
        else None
    )
    legacy_wp_id_raw = doc.get("legacy_wp_id") or (doc.get("metadata", {}) if isinstance(doc.get("metadata"), dict) else {}).get("legacy_wp_id")
    legacy_wp_id: Optional[int] = None
    if legacy_wp_id_raw not in (None, ""):
        try:
            legacy_wp_id = int(str(legacy_wp_id_raw).strip())
        except Exception:
            legacy_wp_id = None
    if existing is None and legacy_wp_id is not None and should_probe_existing:
        existing = fetch_wp_post_by_id(base_url, user, password, legacy_wp_id)
        if existing is None and update_only:
            existing = {"id": legacy_wp_id, "slug": str(doc.get("slug") or ""), "status": "draft"}
    payload = normalize_post_payload(doc, base_url, user, password, dry_run=dry_run, link_lookup=link_lookup)
    metadata = extract_doc_metadata(doc)
    coauthor_names = normalize_coauthor_names(metadata.get("coauthors") or [])
    if not coauthor_names and metadata.get("author"):
        coauthor_names = normalize_coauthor_names([metadata["author"]])

    if update_only and not existing:
        raise RuntimeError(f"Skipped create for slug '{payload.get('slug', doc.get('slug', 'unknown'))}' because --update-only is enabled and no existing post was found.")

    if existing:
        payload["id"] = existing["id"]
        payload["status"] = payload.get("status") or existing.get("status", "draft")
        payload["slug"] = existing.get("slug", payload["slug"])
        payload.pop("author", None)

    if dry_run:
        return {
            "dry_run": True,
            "action": "create" if not existing else "update",
            "slug": payload["slug"],
            "title": payload["title"],
            "doc": make_json_safe(doc),
            "payload": make_json_safe(payload),
        }

    path = "wp-json/wp/v2/posts"
    if existing:
        path = f"wp-json/wp/v2/posts/{existing['id']}"

    response = rest_request(base_url, user, password, path, method="POST", payload=payload)
    post_id = response.get("id") if isinstance(response, dict) else (existing["id"] if existing else None)
    coauthor_assignment = assign_post_coauthors(base_url, user, password, post_id, coauthor_names) if post_id else None
    return {"dry_run": False, "action": "create" if not existing else "update", "slug": payload["slug"], "title": payload["title"], "response": response, "coauthor_assignment": coauthor_assignment}


def run_import(
    manifest_path: Path = DOCS_CACHE_PATH,
    dry_run: bool = False,
    base_url: str = DEFAULT_BASE_URL,
    user: str = DEFAULT_USER,
    password: str = DEFAULT_PASSWORD,
    limit: Optional[int] = None,
    include_low_quality: bool = False,
    blog_sources_root: Optional[Path] = None,
    update_only: bool = False,
    offset: int = 0,
    window_size: Optional[int] = None,
) -> Dict[str, Any]:
    if blog_sources_root is not None:
        all_docs = build_blog_documents_from_sources(blog_sources_root)
        source_label = str(blog_sources_root)
    else:
        manifest = load_json(manifest_path)
        all_docs = manifest.get("documents", [])
        source_label = str(manifest_path)
    filtered_docs = []
    skipped = []
    for doc in all_docs:
        suitability = doc.get("suitability") if isinstance(doc.get("suitability"), dict) else {}
        readiness = suitability.get("readiness") if isinstance(suitability, dict) else None
        if not include_low_quality and readiness and readiness != "ready":
            skipped.append({"title": doc.get("title"), "slug": doc.get("slug"), "suitability": suitability})
            continue
        filtered_docs.append(doc)

    start_index = max(int(offset or 0), 0)
    selection_size = window_size if window_size is not None else limit
    if selection_size is not None:
        selection_size = max(int(selection_size), 0)
    if selection_size is None:
        docs = filtered_docs[start_index:]
    else:
        docs = filtered_docs[start_index : start_index + selection_size]

    link_lookup = build_document_link_lookup(all_docs)
    if not dry_run and base_url and user and password:
        try:
            current_user = rest_request(base_url, user, password, "wp-json/wp/v2/users/me")
        except Exception as exc:
            raise RuntimeError(
                "WordPress authentication preflight failed. "
                "Check --user/--password, WP_LOCAL_USER/WP_LOCAL_APP_PASSWORD, or .secure/.wp_connections.json for localhost runs."
            ) from exc
        capabilities = current_user.get("capabilities") if isinstance(current_user, dict) else {}
        if isinstance(capabilities, dict) and not update_only:
            can_create = bool(capabilities.get("create_posts") or capabilities.get("edit_posts") or capabilities.get("publish_posts"))
            if not can_create:
                raise RuntimeError(
                    "WordPress user is authenticated but lacks create/edit post capabilities for live imports. "
                    "Use --update-only or an account with Author/Editor/Admin rights."
                )
    if not dry_run and base_url and user and password:
        prewarm_terms_for_docs(base_url, user, password, docs)
    results = []
    failed = []
    for doc in docs:
        try:
            prepared_doc = dict(doc)
            metadata = resolve_document_metadata(prepared_doc)
            classification = apply_source_tags_and_categories(prepared_doc, metadata)
            prepared_doc["tags"] = classification["tags"]
            prepared_doc["categories"] = classification["categories"]
            prepared_doc.setdefault("metadata", {})
            if isinstance(prepared_doc["metadata"], dict):
                prepared_doc["metadata"].setdefault("source_path", metadata["source_path"])
                prepared_doc["metadata"].setdefault("source_origin", metadata["source_origin"])
                prepared_doc["metadata"].setdefault("status", metadata["status"])
                prepared_doc["metadata"].setdefault("category", classification["categories"][0] if classification["categories"] else "")
            media_bundle = upload_media_bundle(prepared_doc, base_url, user, password, dry_run=dry_run)
            prepared_doc["media_bundle"] = media_bundle
            prepared_doc["rendered_content"] = render_document_content(
                prepared_doc,
                base_url=base_url,
                user=user,
                password=password,
                dry_run=dry_run,
                link_lookup=link_lookup,
            )
            result = upsert_wordpress_post(
                base_url,
                user,
                password,
                prepared_doc,
                dry_run=dry_run,
                link_lookup=link_lookup,
                update_only=update_only,
            )
            persist_import_state(prepared_doc, result, media_bundle)
            results.append(result)
        except Exception as exc:
            failed.append(
                {
                    "title": doc.get("title"),
                    "slug": doc.get("slug"),
                    "error": str(exc),
                }
            )

    return {
        "source": source_label,
        "count": len(docs),
        "dry_run": dry_run,
        "offset": start_index,
        "window_size": selection_size,
        "total_filtered": len(filtered_docs),
        "skipped": make_json_safe(skipped),
        "results": make_json_safe(results),
        "failed": make_json_safe(failed),
    }



def write_utf8(text: str) -> None:
    try:
        sys.stdout.reconfigure(encoding="utf-8")
    except Exception:
        pass
    try:
        sys.stdout.buffer.write(text.encode("utf-8"))
        sys.stdout.buffer.write(b"\n")
        sys.stdout.buffer.flush()
    except Exception:
        sys.stdout.write(text)
        sys.stdout.write("\n")
        sys.stdout.flush()


def main() -> None:
    global RUNTIME_REST_ROUTE_STYLE
    parser = argparse.ArgumentParser(description="Import the normalized docs manifest into WordPress using the REST API")
    parser.add_argument("--manifest", default=str(DOCS_CACHE_PATH), help="Manifest file to import")
    parser.add_argument("--blog-sources-root", default=None, help="Optional page-content-blog sources root (supports */article.md + meta.json and legacy folders with .md files)")
    parser.add_argument("--base-url", default=DEFAULT_BASE_URL, help="WordPress base URL")
    parser.add_argument("--user", default=DEFAULT_USER, help="WordPress user")
    parser.add_argument("--password", default=DEFAULT_PASSWORD, help="Application password")
    parser.add_argument("--dry-run", action="store_true", help="Compute what would be created/updated without sending requests")
    parser.add_argument("--offset", type=int, default=0, help="Start processing from this filtered-document offset")
    parser.add_argument("--limit", type=int, default=None, help="Optional number of docs to process from the offset")
    parser.add_argument("--window-size", "--batch-size", dest="window_size", type=int, default=None, help="Sliding window size for batch processing; equivalent to --limit when set")
    parser.add_argument("--include-low-quality", action="store_true", help="Process docs classified as needs_review or blocked")
    parser.add_argument("--update-only", action="store_true", help="Only update existing posts; skip creates when no post matches source slug/path/title")
    parser.add_argument("--json", action="store_true", help="Print JSON result")
    args = parser.parse_args()

    blog_root = Path(args.blog_sources_root).resolve() if args.blog_sources_root else None
    resolved_credentials = resolve_runtime_credentials(args.base_url, args.user, args.password)
    RUNTIME_REST_ROUTE_STYLE = str(resolved_credentials.get("rest_route_style") or RUNTIME_REST_ROUTE_STYLE).strip().lower() or RUNTIME_REST_ROUTE_STYLE

    result = run_import(
        Path(args.manifest),
        dry_run=args.dry_run,
        base_url=args.base_url,
        user=resolved_credentials["user"],
        password=resolved_credentials["password"],
        limit=args.limit if args.window_size is None else None,
        include_low_quality=args.include_low_quality,
        blog_sources_root=blog_root,
        update_only=args.update_only,
        offset=args.offset,
        window_size=args.window_size,
    )
    if args.json:
        payload = {
            "source": result["source"],
            "count": result["count"],
            "dry_run": result["dry_run"],
            "results": result["results"][:10],
            "failed": result.get("failed", [])[:10],
        }
        write_utf8(json.dumps(payload, ensure_ascii=False, indent=2))
        return

    write_utf8(
        f"[docs-import] total={result['count']} dry_run={result['dry_run']} "
        f"succeeded={len(result['results'])} failed={len(result.get('failed', []))}"
    )
    for entry in result["results"][:10]:
        write_utf8(f"  - {entry['action']}: {entry['title']} ({entry['slug']})")
    for entry in result.get("failed", [])[:10]:
        write_utf8(f"  - failed: {entry.get('title')} ({entry.get('slug')}) -> {entry.get('error')}")


if __name__ == "__main__":
    main()
