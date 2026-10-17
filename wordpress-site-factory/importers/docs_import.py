from __future__ import annotations

import argparse
import json
import os
import sys
from datetime import datetime, timezone
from pathlib import Path
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
    try:
        posts = rest_request(base_url, user, password, "wp-json/wp/v2/posts?search=" + source_path + "&per_page=20")
    except Exception:
        return []
    if not isinstance(posts, list):
        return []
    return [item for item in posts if isinstance(item, dict)]


def existing_wp_doc(base_url: str, user: str, password: str, source_path: str, slug: str) -> Optional[Dict[str, Any]]:
    matches = fetch_posts_by_source_path(base_url, user, password, source_path)
    for post in matches:
        if post.get("slug") == slug:
            return post
    if matches:
        return matches[0]

    try:
        posts = rest_request(base_url, user, password, "wp-json/wp/v2/posts?slug=" + slug + "&per_page=20")
    except Exception:
        return None
    if isinstance(posts, list) and posts:
        return posts[0]
    return None


def ensure_taxonomy_terms(tags: Iterable[str]) -> List[str]:
    taxonomy = load_taxonomy()
    allowed = set(taxonomy.get("selection_tags", [])) | set(taxonomy.get("restricted_tags", [])) | set(taxonomy.get("descriptive_tags", []))
    selected: List[str] = []
    seen = set()
    for tag in tags:
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
    for tag in tags:
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


def strip_frontmatter(text: str) -> str:
    if not text.startswith("---"):
        return text
    match = __import__("re").match(r"^---\s*\n.*?\n---\s*(?:\n|$)", text, __import__("re").DOTALL)
    if not match:
        return text
    return text[match.end():]


def markdown_to_html(markdown: str) -> str:
    text = strip_frontmatter(markdown or "").strip()
    if not text:
        return ""

    import re

    def escape_html(value: str) -> str:
        return (
            value.replace("&", "&amp;")
            .replace("<", "&lt;")
            .replace(">", "&gt;")
            .replace('"', "&quot;")
            .replace("'", "&#039;")
        )

    def replace_inline(value: str) -> str:
        value = re.sub(r"`([^`]+)`", r"<code>\1</code>", value)
        value = re.sub(r"\[([^\]]+)\]\(([^)]+)\)", r'<a href="\2">\1</a>', value)
        value = re.sub(r"\*\*([^*]+)\*\*", r"<strong>\1</strong>", value)
        value = re.sub(r"\*([^*]+)\*", r"<em>\1</em>", value)
        return value

    lines = text.splitlines()
    out: List[str] = []
    i = 0
    while i < len(lines):
        line = lines[i].rstrip()
        if not line.strip():
            i += 1
            continue
        if line.startswith("```"):
            fence = line[3:].strip()
            i += 1
            code_lines: List[str] = []
            while i < len(lines) and not lines[i].startswith("```"):
                code_lines.append(lines[i])
                i += 1
            if i < len(lines):
                i += 1
            code_text = escape_html("\n".join(code_lines))
            out.append(f"<pre><code class=\"language-{escape_html(fence)}\">{code_text}</code></pre>")
            continue
        if re.match(r"^(#{1,6})\s+", line):
            level = len(re.match(r"^(#{1,6})\s+", line).group(1))
            body = re.sub(r"^(#{1,6})\s+", "", line).strip()
            out.append(f"<h{level}>{replace_inline(escape_html(body))}</h{level}>")
            i += 1
            continue
        if re.match(r"^[-*+]\s+", line):
            items: List[str] = []
            while i < len(lines) and re.match(r"^[-*+]\s+", lines[i]):
                items.append(re.sub(r"^[-*+]\s+", "", lines[i]).strip())
                i += 1
            out.append("<ul>" + "".join(f"<li>{replace_inline(escape_html(item))}</li>" for item in items if item) + "</ul>")
            continue
        if re.match(r"^\d+\.\s+", line):
            items: List[str] = []
            while i < len(lines) and re.match(r"^\d+\.\s+", lines[i]):
                items.append(re.sub(r"^\d+\.\s+", "", lines[i]).strip())
                i += 1
            out.append("<ol>" + "".join(f"<li>{replace_inline(escape_html(item))}</li>" for item in items if item) + "</ol>")
            continue
        if re.match(r"^>\s+", line):
            quote_text = re.sub(r"^>\s+", "", line).strip()
            out.append(f"<blockquote><p>{replace_inline(escape_html(quote_text))}</p></blockquote>")
            i += 1
            continue

        paragraph: List[str] = []
        while i < len(lines):
            current = lines[i].rstrip()
            if not current.strip():
                break
            if current.startswith("```") or re.match(r"^(#{1,6})\s+", current) or re.match(r"^[-*+]\s+", current) or re.match(r"^\d+\.\s+", current) or re.match(r"^>\s+", current):
                break
            paragraph.append(current.strip())
            i += 1
        if paragraph:
            text_block = " ".join(part for part in paragraph if part)
            out.append(f"<p>{replace_inline(escape_html(text_block))}</p>")
        else:
            i += 1

    return "\n".join(out)


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


def normalize_post_payload(doc: Dict[str, Any], base_url: str = "", user: str = "", password: str = "", dry_run: bool = False) -> Dict[str, Any]:
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
    source_html = markdown_to_html(source_markdown) if source_markdown else ""
    coauthor_names = list(dict.fromkeys([str(name).strip() for name in (metadata.get("coauthors") or []) if str(name).strip()]))
    coauthor_csv = ", ".join(coauthor_names)
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
            "themisdb_date": metadata["date"] or metadata["published_at"] or metadata["created_at"] or "",
            "themisdb_created_at": metadata["created_at"] or "",
            "themisdb_modified_at": metadata["modified_at"] or "",
            "themisdb_published_at": metadata["published_at"] or "",
            "themisdb_language": metadata["language"],
            "themisdb_source_path": metadata["source_path"],
            "themisdb_source_origin": metadata["source_origin"],
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
    }, ensure_ascii=False)
    return payload


def create_or_update_document(base_url: str, user: str, password: str, doc: Dict[str, Any], dry_run: bool = False) -> Dict[str, Any]:
    existing = existing_wp_doc(base_url, user, password, doc.get("source_path", ""), doc.get("slug", "")) if not dry_run else None
    payload = normalize_post_payload(doc, base_url, user, password, dry_run=dry_run)

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
    return {"dry_run": False, "action": "create" if not existing else "update", "slug": payload["slug"], "title": payload["title"], "response": response}


def run_import(manifest_path: Path = DOCS_CACHE_PATH, dry_run: bool = False, base_url: str = DEFAULT_BASE_URL, user: str = DEFAULT_USER, password: str = DEFAULT_PASSWORD, limit: Optional[int] = None) -> Dict[str, Any]:
    manifest = load_json(manifest_path)
    docs = manifest.get("documents", [])
    if limit is not None:
        docs = docs[:limit]
    results = []
    for doc in docs:
        results.append(create_or_update_document(base_url, user, password, doc, dry_run=dry_run))

    return {"source": str(manifest_path), "count": len(docs), "dry_run": dry_run, "results": results}



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
    parser.add_argument("--json", action="store_true", help="Print JSON result")
    args = parser.parse_args()

    result = run_import(Path(args.manifest), dry_run=args.dry_run, base_url=args.base_url, user=args.user, password=args.password, limit=args.limit)
    if args.json:
        payload = {"source": result["source"], "count": result["count"], "dry_run": result["dry_run"], "results": result["results"][:10]}
        write_utf8(json.dumps(payload, ensure_ascii=False, indent=2))
        return

    write_utf8(f"[docs-import] total={result['count']} dry_run={result['dry_run']}")
    for entry in result["results"][:10]:
        write_utf8(f"  - {entry['action']}: {entry['title']} ({entry['slug']})")


if __name__ == "__main__":
    main()
