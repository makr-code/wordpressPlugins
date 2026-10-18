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

from PIL import Image
from datetime import datetime, timezone
from pathlib import Path, PurePosixPath
from typing import Any, Dict, Iterable, List, Optional
from urllib.parse import quote

import requests

ROOT = Path(__file__).resolve().parents[1]
DOCS_CACHE_PATH = ROOT / "output" / "docs_cache.json"
TAXONOMY_PATH = ROOT / "config" / "taxonomy.json"
SITE_CONFIG_PATH = ROOT / "config" / "site.json"


def load_site_config() -> Dict[str, Any]:
    if not SITE_CONFIG_PATH.exists():
        return {}
    try:
        return json.loads(SITE_CONFIG_PATH.read_text(encoding="utf-8"))
    except Exception:
        return {}


DEFAULT_BASE_URL = os.environ.get("WP_BASE_URL") or load_site_config().get("wordpress", {}).get("base_url", "http://localhost/wordpress")
DEFAULT_USER = os.environ.get("WP_USER") or load_site_config().get("wordpress", {}).get("user", "test")
DEFAULT_PASSWORD = os.environ.get("WP_APP_PASSWORD") or load_site_config().get("wordpress", {}).get("app_password", "")


def load_json(path: Path) -> Dict[str, Any]:
    if not path.exists():
        return {"documents": []}
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return {"documents": []}


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


def build_wp_url(base_url: str, path: str) -> str:
    return base_url.rstrip("/") + "/" + path.lstrip("/")


def rest_request(base_url: str, user: str, password: str, path: str, method: str = "GET", payload: Optional[Dict[str, Any]] = None) -> Any:
    url = build_wp_url(base_url, path)
    auth = (user, password) if user and password else None
    response = requests.request(method=method, url=url, auth=auth, json=payload, timeout=30)
    if response.status_code >= 400:
        raise RuntimeError(f"REST call failed: {method} {url} -> {response.status_code}: {response.text[:500]}")
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
    for query in (
        "wp-json/wp/v2/posts?search=" + quote(source_path) + "&per_page=20",
        "wp-json/wp/v2/posts?meta_key=themisdb_source_path&meta_value=" + quote(source_path) + "&per_page=20",
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
            posts = rest_request(base_url, user, password, "wp-json/wp/v2/posts?slug=" + quote(slug) + "&per_page=20")
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
            posts = rest_request(base_url, user, password, "wp-json/wp/v2/posts?search=" + quote(title) + "&per_page=20")
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


def ensure_term_id(base_url: str, user: str, password: str, taxonomy: str, term_name: str) -> int:
    slug = term_name.strip().lower().replace("_", "-")
    search_path = f"wp-json/wp/v2/{taxonomy}?search={slug}&per_page=20"
    results = rest_request(base_url, user, password, search_path)
    if isinstance(results, list):
        for item in results:
            if isinstance(item, dict) and item.get("slug") == slug:
                return int(item["id"])
    create_payload = {"name": term_name, "slug": slug}
    created = rest_request(base_url, user, password, f"wp-json/wp/v2/{taxonomy}", method="POST", payload=create_payload)
    if isinstance(created, dict) and "id" in created:
        return int(created["id"])
    raise RuntimeError(f"Unable to resolve taxonomy term '{term_name}' for {taxonomy}")


def resolve_term_ids(base_url: str, user: str, password: str, taxonomy: str, term_names: Iterable[str]) -> List[int]:
    ids: List[int] = []
    seen = set()
    for name in term_names:
        slug = str(name).strip().lower().replace("_", "-")
        if not slug or slug in seen:
            continue
        seen.add(slug)
        ids.append(ensure_term_id(base_url, user, password, taxonomy, name))
    return ids


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

    for match in re.finditer(r"```\s*mermaid\s*\n(.*?)\n```", text, flags=re.IGNORECASE | re.DOTALL):
        payload = match.group(1).strip()
        if payload:
            assets.append({"type": "mermaid", "raw": match.group(0), "target": payload})

    for match in re.finditer(r"!\[[^\]]*\]\(([^)]+)\)", text):
        raw_target = match.group(1).strip()
        if not raw_target or raw_target.startswith(("http://", "https://", "data:", "#", "mailto:")):
            continue
        cleaned = raw_target.split()[0].strip()
        if cleaned:
            assets.append({"type": "image", "raw": match.group(0), "target": cleaned})

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


def upload_media_asset(base_url: str, user: str, password: str, local_path: str, title: str = "", alt_text: str = "") -> str:
    path = Path(local_path)
    if not path.exists():
        raise FileNotFoundError(f"Asset not found: {local_path}")

    mime_types = {
        ".jpg": "image/jpeg",
        ".jpeg": "image/jpeg",
        ".png": "image/png",
        ".gif": "image/gif",
        ".svg": "image/svg+xml",
        ".webp": "image/webp",
        ".pdf": "application/pdf",
    }
    mime_type = mime_types.get(path.suffix.lower(), "application/octet-stream")
    with path.open("rb") as upload_file:
        response = requests.post(
            build_wp_url(base_url, "wp-json/wp/v2/media"),
            auth=(user, password) if user and password else None,
            files={"file": (path.name, upload_file, mime_type)},
            data={"title": title or path.stem, "alt_text": alt_text or title or path.stem},
            timeout=60,
        )
    if response.status_code >= 400:
        raise RuntimeError(f"Media upload failed: {response.status_code}: {response.text[:500]}")
    payload = response.json()
    if isinstance(payload, dict):
        source_url = payload.get("source_url") or payload.get("guid", {}).get("rendered")
        if source_url:
            return str(source_url)
    raise RuntimeError(f"Unexpected media upload response for {path.name}: {response.text[:500]}")


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


def render_mermaid_to_svg(mermaid_source: str, output_path: Path) -> Path:
    output_path.parent.mkdir(parents=True, exist_ok=True)
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
        commands.append([mmdc_cmd, "-i", str(input_path), "-o", str(output_path), "-b", "transparent"])
    npx_cmd = shutil.which("npx") or shutil.which("npx.cmd") or "npx"
    commands.append([npx_cmd, "--yes", "@mermaid-js/mermaid-cli", "-i", str(input_path), "-o", str(output_path), "-b", "transparent"])

    last_error = "Mermaid render failed"
    try:
        for command in commands:
            result = subprocess.run(command, capture_output=True, text=True, timeout=180, check=False, env=env)
            if result.returncode == 0 and output_path.exists():
                return output_path
            last_error = result.stderr.strip() or result.stdout.strip() or last_error
        raise RuntimeError(last_error)
    finally:
        if input_path.exists():
            input_path.unlink(missing_ok=True)


def convert_image_for_wordpress(image_path: Path) -> Path:
    if image_path.suffix.lower() != ".png":
        return image_path
    jpeg_path = image_path.with_suffix(".jpg")
    with Image.open(image_path) as img:
        converted = img.convert("RGB") if img.mode in {"RGBA", "LA", "P", "CMYK"} else img
        converted.save(jpeg_path, "JPEG", quality=90)
    return jpeg_path


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
                diagram_path = repo_root / "_tmp" / f"mermaid-{abs(hash(target))}.svg"
                diagram_path.parent.mkdir(parents=True, exist_ok=True)
                render_mermaid_to_svg(target, diagram_path)
                uploaded_url = upload_media_asset(base_url, user, password, str(diagram_path), "Mermaid diagram", "Mermaid diagram")
                replacement = f'<figure class="themisdb-mermaid"><img src="{uploaded_url}" alt="Mermaid diagram" /></figure>'
            except Exception:
                replacement = f'<pre class="themisdb-mermaid-fallback">{html_lib.escape(target)}</pre>'
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

        if line.startswith("|") and i + 1 < len(lines):
            table_lines = [line]
            j = i + 1
            while j < len(lines) and lines[j].strip().startswith("|"):
                table_lines.append(lines[j].strip())
                j += 1
            if len(table_lines) >= 2:
                rows = []
                for row in table_lines:
                    cells = [part.strip() for part in row.strip().strip("|").split("|")]
                    if cells and any(cell for cell in cells):
                        rows.append(cells)
                if len(rows) >= 2:
                    header = rows[0]
                    candidate_separator = rows[1] if len(rows) > 1 else []
                    if candidate_separator and all(re.fullmatch(r":?-{3,}:?", str(cell).strip()) for cell in candidate_separator):
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
    for block in blocks:
        if not isinstance(block, dict):
            continue
        block_type = str(block.get("type", "")).strip()
        if block_type == "heading":
            level = int(block.get("level") or 1)
            rendered.append(f"<h{level}>{inline_markup(str(block.get('text', '')))}</h{level}>")
        elif block_type == "paragraph":
            rendered.append(f"<p>{inline_markup(str(block.get('text', '')))}</p>")
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
            rendered.append(f"<figure class=\"themisdb-mermaid\"><pre>{escape_html(code)}</pre>{caption_html}</figure>")
        elif block_type == "code":
            language = str(block.get("language", "text")).strip()
            code = str(block.get("code", "")).strip()
            rendered.append(f"<pre><code class=\"language-{escape_html(language)}\">{escape_html(code)}</code></pre>")
        elif block_type == "blockquote":
            rendered.append(f"<blockquote><p>{inline_markup(str(block.get('text', '')))}</p></blockquote>")
        elif block_type == "callout":
            variant = str(block.get("variant", "note")).strip() or "note"
            title = str(block.get("title", "")).strip()
            text = str(block.get("text", "")).strip()
            title_html = f"<strong>{escape_html(title)}</strong>" if title else ""
            rendered.append(f"<div class=\"callout callout-{escape_html(variant)}\">{title_html}<p>{inline_markup(text)}</p></div>")
        elif block_type == "html":
            rendered.append(str(block.get("html", "")))

    return f'<div class="themisdb-formula-content">{"".join(rendered)}</div>' if rendered else ""


def strip_duplicate_leading_h1(html_content: str, title: str) -> str:
    if not html_content or not title:
        return html_content
    normalized_title = re.sub(r"\s+", " ", html_lib.unescape(title).replace("&nbsp;", " ")).strip().lower()
    match = re.search(r"(?is)<h1\b[^>]*>(.*?)</h1>", html_content)
    if not match:
        return html_content
    heading_text = re.sub(r"<[^>]+>", " ", match.group(1))
    heading_text = html_lib.unescape(heading_text).replace("&nbsp;", " ")
    heading_text = re.sub(r"\s+", " ", heading_text).strip().lower()
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
    }


def read_document_body(doc: Dict[str, Any]) -> str:
    source_path = doc.get("source_path") or doc.get("relative_path")
    if not source_path:
        return ""
    try:
        return Path(source_path).read_text(encoding="utf-8", errors="replace")
    except Exception:
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
        "Google Gemini": "cap-google-gemini",
        "Gemini": "cap-google-gemini",
        "GitHub": "cap-github-copilot",
        "GitHub Copilot": "cap-github-copilot",
        "Gemma4 (Lektor)": "cap-gemma4-lektor",
        "SwarmUI (Grafiker)": "cap-swarmui-grafiker",
        "Anthropic Claude": "cap-anthropic-claude",
        "Claude": "cap-anthropic-claude",
        "OpenAI GPT-4o": "cap-openai-gpt-4o",
        "GPT-4o": "cap-openai-gpt-4o",
        "Meta Llama 3": "cap-meta-llama-3",
        "Llama": "cap-meta-llama-3",
        "Mistral Large": "cap-mistral-large",
        "Mistral": "cap-mistral-large",
        "DeepSeek R1": "cap-deepseek-r1",
        "DeepSeek": "cap-deepseek-r1",
        "OpenAI DALL-E": "cap-openai-dall-e",
        "DALL-E": "cap-openai-dall-e",
        "Stable Diffusion": "cap-stable-diffusion",
        "Midjourney": "cap-midjourney",
        "Black Forest Labs Flux": "cap-blackforest-flux",
        "Flux": "cap-blackforest-flux",
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


def assign_post_coauthors(base_url: str, user: str, password: str, post_id: Any, coauthor_names: Iterable[str]) -> Optional[Dict[str, Any]]:
    names = normalize_coauthor_names(coauthor_names or [])
    if not post_id or not names:
        return None
    try:
        return rest_request(base_url, user, password, f"wp-json/coauthors/v1/authors/{post_id}", method="POST", payload={"new_authors": ", ".join(names)})
    except Exception:
        return None


def normalize_post_payload(doc: Dict[str, Any], base_url: str = "", user: str = "", password: str = "", dry_run: bool = False, link_lookup: Optional[Dict[str, str]] = None) -> Dict[str, Any]:
    taxonomy = load_taxonomy()
    raw_tags = ensure_taxonomy_terms(doc.get("tags", []))[:5]
    tags = list(dict.fromkeys(raw_tags))
    public_selection = [tag for tag in tags if tag in taxonomy.get("selection_tags", [])]
    descriptive = [tag for tag in tags if tag in taxonomy.get("descriptive_tags", [])]
    category_names = [primary_category_for_tags(tags)] if tags else ["documentation"]
    if not public_selection and descriptive:
        category_names = [descriptive[0]]
    custom_taxonomies = extract_custom_taxonomy_values(doc)
    metadata = extract_doc_metadata(doc)
    author_id = resolve_author_user_id(base_url, user, password, metadata["author"]) if base_url and user and password else None
    source_markdown = read_document_body(doc)
    source_markdown_for_html = rewrite_markdown_assets(
        source_markdown,
        current_relative_path=str(doc.get("relative_path") or ""),
        base_url=base_url,
        user=user,
        password=password,
        repo_root=ROOT,
    ) if source_markdown else ""
    source_html = markdown_to_html(source_markdown_for_html, current_relative_path=str(doc.get("relative_path") or ""), doc_lookup=link_lookup, base_url=base_url) if source_markdown_for_html else ""
    source_html = strip_duplicate_leading_h1(source_html, str(doc.get("title") or ""))
    coauthor_names = normalize_coauthor_names(metadata.get("coauthors") or [])
    if not coauthor_names and metadata.get("author"):
        coauthor_names = normalize_coauthor_names([metadata["author"]])
    coauthor_csv = ", ".join(coauthor_names)
    coauthor_ids = resolve_guest_author_ids(base_url, user, password, coauthor_names) if base_url and user and password else []
    content_body = source_html or f"<h2>{doc.get('title', 'Document')}</h2><p>Source: {doc.get('relative_path', '')}</p>"

    payload = {
        "title": doc.get("title", "Untitled document"),
        "slug": doc.get("slug", "document"),
        "status": "publish",
        "content": content_body,
        "excerpt": build_excerpt(doc.get("title", "Untitled document"), tags),
        "date": metadata["date"] or metadata["published_at"] or metadata["created_at"],
        "date_gmt": (metadata["date"] or metadata["published_at"] or metadata["created_at"]).replace("+00:00", "+00:00") if (metadata["date"] or metadata["published_at"] or metadata["created_at"]) else None,
        "modified": metadata["modified_at"] or metadata["date"] or metadata["created_at"],
        "modified_gmt": (metadata["modified_at"] or metadata["date"] or metadata["created_at"]).replace("+00:00", "+00:00") if (metadata["modified_at"] or metadata["date"] or metadata["created_at"]) else None,
        "author": author_id if author_id is not None else 1,
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
    if custom_taxonomies:
        payload["tax_input"] = {taxonomy_name: list(values) for taxonomy_name, values in custom_taxonomies.items()}
    if dry_run:
        return payload

    payload["tags"] = resolve_term_ids(base_url, user, password, "tags", tags)
    payload["categories"] = resolve_term_ids(base_url, user, password, "categories", category_names)
    payload["author"] = author_id if author_id is not None else 1
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


def create_or_update_document(base_url: str, user: str, password: str, doc: Dict[str, Any], dry_run: bool = False, link_lookup: Optional[Dict[str, str]] = None) -> Dict[str, Any]:
    existing = existing_wp_doc(base_url, user, password, doc.get("source_path", ""), doc.get("slug", ""), doc.get("title", "")) if not dry_run else None
    payload = normalize_post_payload(doc, base_url, user, password, dry_run=dry_run, link_lookup=link_lookup)
    metadata = extract_doc_metadata(doc)
    coauthor_names = normalize_coauthor_names(metadata.get("coauthors") or [])
    if not coauthor_names and metadata.get("author"):
        coauthor_names = normalize_coauthor_names([metadata["author"]])

    if existing:
        payload["id"] = existing["id"]
        payload["status"] = existing.get("status", "draft")
        payload["slug"] = existing.get("slug", payload["slug"])

    if dry_run:
        return {
            "dry_run": True,
            "action": "create" if not existing else "update",
            "slug": payload["slug"],
            "title": payload["title"],
            "doc": doc,
            "payload": payload,
        }

    path = "wp-json/wp/v2/posts"
    if existing:
        path = f"wp-json/wp/v2/posts/{existing['id']}"

    response = rest_request(base_url, user, password, path, method="POST", payload=payload)
    post_id = response.get("id") if isinstance(response, dict) else (existing["id"] if existing else None)
    coauthor_assignment = assign_post_coauthors(base_url, user, password, post_id, coauthor_names) if post_id else None
    return {"dry_run": False, "action": "create" if not existing else "update", "slug": payload["slug"], "title": payload["title"], "response": response, "coauthor_assignment": coauthor_assignment}


def run_import(manifest_path: Path = DOCS_CACHE_PATH, dry_run: bool = False, base_url: str = DEFAULT_BASE_URL, user: str = DEFAULT_USER, password: str = DEFAULT_PASSWORD, limit: Optional[int] = None, include_low_quality: bool = False) -> Dict[str, Any]:
    manifest = load_json(manifest_path)
    all_docs = manifest.get("documents", [])
    filtered_docs = []
    skipped = []
    for doc in all_docs:
        suitability = doc.get("suitability") if isinstance(doc.get("suitability"), dict) else {}
        readiness = suitability.get("readiness") if isinstance(suitability, dict) else None
        if not include_low_quality and readiness and readiness != "ready":
            skipped.append({"title": doc.get("title"), "slug": doc.get("slug"), "suitability": suitability})
            continue
        filtered_docs.append(doc)
    docs = filtered_docs if limit is None else filtered_docs[:limit]
    link_lookup = build_document_link_lookup(all_docs)
    results = []
    for doc in docs:
        results.append(create_or_update_document(base_url, user, password, doc, dry_run=dry_run, link_lookup=link_lookup))

    return {"source": str(manifest_path), "count": len(docs), "dry_run": dry_run, "skipped": skipped, "results": results}



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
    parser = argparse.ArgumentParser(description="Import the normalized docs manifest into WordPress using the REST API")
    parser.add_argument("--manifest", default=str(DOCS_CACHE_PATH), help="Manifest file to import")
    parser.add_argument("--base-url", default=DEFAULT_BASE_URL, help="WordPress base URL")
    parser.add_argument("--user", default=DEFAULT_USER, help="WordPress user")
    parser.add_argument("--password", default=DEFAULT_PASSWORD, help="Application password")
    parser.add_argument("--dry-run", action="store_true", help="Compute what would be created/updated without sending requests")
    parser.add_argument("--limit", type=int, default=None, help="Optional number of docs to process")
    parser.add_argument("--include-low-quality", action="store_true", help="Process docs classified as needs_review or blocked")
    parser.add_argument("--json", action="store_true", help="Print JSON result")
    args = parser.parse_args()

    result = run_import(Path(args.manifest), dry_run=args.dry_run, base_url=args.base_url, user=args.user, password=args.password, limit=args.limit, include_low_quality=args.include_low_quality)
    if args.json:
        payload = {"source": result["source"], "count": result["count"], "dry_run": result["dry_run"], "results": result["results"][:10]}
        write_utf8(json.dumps(payload, ensure_ascii=False, indent=2))
        return

    write_utf8(f"[docs-import] total={result['count']} dry_run={result['dry_run']}")
    for entry in result["results"][:10]:
        write_utf8(f"  - {entry['action']}: {entry['title']} ({entry['slug']})")


if __name__ == "__main__":
    main()
