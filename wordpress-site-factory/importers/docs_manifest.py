from __future__ import annotations

import argparse
import hashlib
import json
import re
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, Iterable, List, Tuple

ROOT = Path(__file__).resolve().parents[1]
SOURCE_ROOT = ROOT.parent / "source-links" / "docs"
OUTPUT_DIR = ROOT / "output"
DEFAULT_CACHE_PATH = OUTPUT_DIR / "docs_cache.json"
TAXONOMY_PATH = ROOT / "config" / "taxonomy.json"

EXCLUDED_DIRS = {"_generated", "_standards", "ARCHIVED", ".git"}
ALLOWED_EXTENSIONS = {".md", ".markdown", ".txt", ".html", ".htm", ".json", ".yaml", ".yml"}

TAG_RULES = [
    ("documentation", ["doc", "docs", "guide", "readme", "documentation", "manual", "index"]),
    ("architecture", ["architecture", "design", "system", "graph", "diagram", "whitepaper"]),
    ("api", ["api", "openapi", "rest", "rpc", "transport"]),
    ("release", ["release", "version", "changelog", "roadmap"]),
    ("support", ["support", "troubleshooting", "faq", "help", "runbook"]),
    ("security", ["security", "auth", "audit", "compliance"]),
    ("benchmark", ["benchmark", "performance", "testing", "metrics"]),
    ("community", ["community", "governance", "contributing"]),
    ("how-to", ["quickstart", "setup", "installation", "configuration"]),
]


def load_allowed_tags() -> set[str]:
    if not TAXONOMY_PATH.exists():
        return {tag for tag, _ in TAG_RULES}
    try:
        data = json.loads(TAXONOMY_PATH.read_text(encoding="utf-8"))
    except Exception:
        return {tag for tag, _ in TAG_RULES}
    values: List[str] = []
    for key in ("selection_tags", "restricted_tags", "descriptive_tags"):
        values.extend(data.get(key, []))
    return {str(tag).strip() for tag in values if str(tag).strip()}


def normalize_tags(tags: Iterable[str], max_tags: int = 5) -> List[str]:
    allowed = load_allowed_tags()
    dedup: List[str] = []
    seen = set()
    for tag in tags:
        value = str(tag).strip().lower().replace("_", "-")
        if not value or value in seen:
            continue
        if value in allowed:
            dedup.append(value)
            seen.add(value)
    if not dedup:
        dedup = ["documentation"]
    return dedup[:max_tags]


def slugify(value: str) -> str:
    text = value.strip().lower()
    text = text.replace("ä", "ae").replace("ö", "oe").replace("ü", "ue").replace("ß", "ss")
    text = re.sub(r"[^a-z0-9]+", "-", text)
    text = re.sub(r"-+", "-", text).strip("-")
    return text or "document"


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(65536), b""):
            digest.update(chunk)
    return digest.hexdigest()


def read_text(path: Path) -> str:
    try:
        return path.read_text(encoding="utf-8", errors="replace")
    except Exception:
        return ""


def infer_title(path: Path, text: str) -> str:
    body = text
    if body.startswith("---"):
        body = re.sub(r"^---\s*\n.*?\n---\s*\n?", "", body, flags=re.DOTALL)
    patterns = [
        (r"^#\s+(.+)$", re.IGNORECASE | re.MULTILINE),
        (r"^title:\s*(.+)$", re.IGNORECASE | re.MULTILINE),
        (r"<title>(.+?)</title>", re.IGNORECASE | re.DOTALL),
    ]
    for pattern, flags in patterns:
        match = re.search(pattern, body, flags)
        if match:
            title = match.group(1).strip()
            title = re.sub(r"\s+", " ", title)
            if title:
                return title
    stem = path.stem.replace("_", " ").replace("-", " ")
    return stem.title()


def infer_kind(path: Path, text: str) -> str:
    lowered = (path.as_posix() + " " + text).lower()
    for kind, keywords in TAG_RULES:
        if any(keyword in lowered for keyword in keywords):
            if kind in {"api", "release", "support", "architecture", "benchmark"}:
                return kind
    return "documentation"


def parse_frontmatter(text: str) -> Dict[str, Any]:
    if not text.startswith("---"):
        return {}
    match = re.match(r"^---\s*\n(.*?)\n---\s*(?:\n|$)", text, re.DOTALL)
    if not match:
        return {}
    block = match.group(1)
    data: Dict[str, Any] = {}
    for line in block.splitlines():
        if not line.strip() or line.lstrip().startswith("#"):
            continue
        if ":" not in line:
            continue
        key, value = line.split(":", 1)
        data[key.strip().lower()] = value.strip()
    return data


def canonicalize_person_name(value: Any) -> str:
    text = str(value).strip()
    text = text.replace("║", "").replace("│", "").replace("`", "")
    text = text.replace("<git-user-name or \"unknown\">", "")
    text = text.replace("[bot", "")
    text = re.sub(r"\s*\[bot.*$", "", text, flags=re.IGNORECASE)
    text = re.sub(r"\s*\(.*?agent.*?\)", "", text, flags=re.IGNORECASE)
    text = text.replace("- ThemisDB Team", "ThemisDB")
    text = text.replace("ThemisDB Contributors", "ThemisDB")
    text = text.replace("ThemisDB Implementer", "ThemisDB")
    aliases = {
        "gemini": "Gemini",
        "gimini": "Gemini",
        "swarmui": "SwarmUI",
        "swarm ui": "SwarmUI",
        "claude": "Claude",
        "copilot": "Copilot",
        "copilot-swe-agent": "Copilot",
        "openai": "OpenAI",
        "anthropic": "Anthropic",
        "github": "GitHub",
        "themisdb team": "ThemisDB",
        "themisdb contributors": "ThemisDB",
        "themisdb implementer": "ThemisDB",
        "themisdb maintainers": "ThemisDB",
        "themisdb (exception safety agent)": "ThemisDB",
    }
    lowered = text.lower().strip()
    if lowered in aliases:
        return aliases[lowered]
    text = re.sub(r"\s+", " ", text)
    text = text.strip("[]{}()<>\"' ")
    text = text.strip(".,;:-/\\")
    if not text or text.lower() in {"unknown", "n/a", "na", "none", "null", "-", "<git-user-name or \"unknown\">"}:
        return ""
    return text


def sanitize_person_name(value: Any) -> str:
    return canonicalize_person_name(value)


def normalize_person_list(value: Any) -> List[str]:
    if value is None:
        return []
    if isinstance(value, list):
        items = value
    elif isinstance(value, str):
        items = re.split(r"[,;\n]|\s+and\s+", value)
    else:
        items = [str(value)]
    names: List[str] = []
    seen = set()
    for item in items:
        name = sanitize_person_name(item)
        if not name:
            continue
        key = name.lower()
        if key in seen:
            continue
        seen.add(key)
        names.append(name)
    return names


def normalize_datetime(value: Any, fallback: datetime | None = None) -> str | None:
    if value is None:
        return fallback.isoformat() if fallback else None
    text = str(value).strip()
    if not text:
        return fallback.isoformat() if fallback else None
    cleaned = text.replace("Z", "+00:00")
    try:
        return datetime.fromisoformat(cleaned).astimezone(timezone.utc).isoformat()
    except ValueError:
        pass

    normalized = cleaned.strip().strip('"\'')
    normalized = normalized.replace('T', ' ')
    normalized = normalized.replace('UTC', '')
    normalized = re.sub(r'\s+', ' ', normalized)
    formats = [
        "%Y-%m-%d %H:%M:%S",
        "%Y-%m-%d",
        "%d.%m.%Y",
        "%d.%m.%Y %H:%M",
        "%Y/%m/%d",
        "%Y/%m/%d %H:%M:%S",
        "%d.%m.%Y %H:%M:%S",
        "%B %d, %Y",
        "%b %d, %Y",
        "%B %Y",
        "%Y-%m",
        "%d %B %Y",
        "%d %b %Y",
    ]
    for fmt in formats:
        try:
            dt = datetime.strptime(normalized, fmt)
            return dt.replace(tzinfo=timezone.utc).isoformat()
        except ValueError:
            continue

    month_map = {
        'januar': 'January', 'februar': 'February', 'märz': 'March', 'april': 'April', 'mai': 'May', 'juni': 'June',
        'juli': 'July', 'august': 'August', 'september': 'September', 'oktober': 'October', 'november': 'November', 'dezember': 'December',
    }
    lower = normalized.lower()
    for key, value in month_map.items():
        if key in lower:
            normalized = normalized.replace(key.capitalize(), value)
            try:
                dt = datetime.strptime(normalized, "%B %Y")
                return dt.replace(tzinfo=timezone.utc).isoformat()
            except ValueError:
                pass

    if fallback:
        return fallback.astimezone(timezone.utc).isoformat()
    return None


def extract_date_metadata(path: Path, text: str) -> Dict[str, Any]:
    frontmatter = parse_frontmatter(text)
    file_mtime = datetime.fromtimestamp(path.stat().st_mtime, timezone.utc)
    values = {
        "date": frontmatter.get("date") or frontmatter.get("published") or frontmatter.get("published_at"),
        "created_at": frontmatter.get("created") or frontmatter.get("created_at") or frontmatter.get("date"),
        "modified_at": frontmatter.get("updated") or frontmatter.get("modified") or frontmatter.get("modified_at") or frontmatter.get("last_updated"),
        "published_at": frontmatter.get("published") or frontmatter.get("published_at") or frontmatter.get("date"),
        "language": frontmatter.get("language") or frontmatter.get("lang") or "und",
    }
    valid = {
        "date": normalize_datetime(values["date"], file_mtime),
        "created_at": normalize_datetime(values["created_at"], file_mtime),
        "modified_at": normalize_datetime(values["modified_at"], file_mtime),
        "published_at": normalize_datetime(values["published_at"], file_mtime),
        "language": values["language"],
    }
    return valid


def extract_related_documents(text: str, relative_path: str) -> List[str]:
    links: List[str] = []
    seen = set()
    for match in re.finditer(r"\[[^\]]+\]\(([^)]+)\)", text):
        target = match.group(1).strip()
        if target.startswith(("http://", "https://", "mailto:", "#")):
            continue
        if not target.lower().endswith((".md", ".markdown", ".txt", ".html", ".htm", ".yaml", ".yml")):
            continue
        cleaned = target.split("#", 1)[0].strip()
        if not cleaned:
            continue
        link_path = (Path(relative_path).parent / cleaned).resolve().as_posix()
        slug = slugify(Path(link_path).stem.replace("_", " "))
        if slug not in seen and slug:
            seen.add(slug)
            links.append(slug)
    return links[:20]


def build_relation_graph(relative_path: str, related_docs: List[str]) -> Dict[str, Any]:
    node_id = slugify(Path(relative_path).stem.replace("_", " ")) or "document"
    nodes = [{"id": node_id, "type": "document"}]
    edges = []
    for target in related_docs:
        nodes.append({"id": target, "type": "document"})
        edges.append({"source": node_id, "target": target, "type": "references"})
    return {"nodes": nodes, "edges": edges}


def infer_metadata(path: Path, text: str, relative_path: str = "") -> Dict[str, Any]:
    author_names: List[str] = []
    coauthors: List[str] = []
    lowered = (path.as_posix() + " " + text).lower()
    frontmatter = parse_frontmatter(text)

    for pattern in (r"(?:^|\n)author\s*[:\-]\s*(.+)", r"(?:^|\n)authors\s*[:\-]\s*(.+)", r"(?:^|\n)co[- ]?authors?\s*[:\-]\s*(.+)", r"(?:^|\n)contributors?\s*[:\-]\s*(.+)"):
        match = re.search(pattern, text, flags=re.IGNORECASE | re.MULTILINE)
        if match:
            value = match.group(1).strip().strip("`[](){}")
            for part in re.split(r"[,;/]|\n", value):
                name = part.strip()
                if name:
                    author_names.append(name)

    for key in ("author", "authors", "maintainer", "owner"):
        if key in frontmatter:
            author_names.extend(normalize_person_list(frontmatter.get(key)))
    for key in ("coauthors", "contributors"):
        if key in frontmatter:
            coauthors.extend(normalize_person_list(frontmatter.get(key)))

    lookup = {
        "gemini": "Gemini",
        "gimini": "Gemini",
        "swarmui": "SwarmUI",
        "swarm ui": "SwarmUI",
        "claude": "Claude",
        "copilot": "Copilot",
        "openai": "OpenAI",
        "anthropic": "Anthropic",
        "cursor": "Cursor",
        "github": "GitHub",
    }
    for token, label in lookup.items():
        if token in lowered:
            coauthors.append(label)

    author_names = [sanitize_person_name(item) for item in author_names]
    coauthors = [sanitize_person_name(item) for item in coauthors]
    author_names = [item for item in author_names if item]
    coauthors = [item for item in coauthors if item]

    if "themisdb" in lowered and "gemini" in lowered:
        author_names.append("ThemisDB")
    if not author_names:
        author_names = ["ThemisDB"]

    primary_author = author_names[0]
    unique_coauthors = []
    seen = {primary_author.lower()}
    for value in coauthors + author_names[1:]:
        normalized = sanitize_person_name(value)
        if not normalized:
            continue
        key = normalized.lower()
        if key in seen:
            continue
        seen.add(key)
        unique_coauthors.append(normalized)

    date_metadata = extract_date_metadata(path, text)
    related = extract_related_documents(text, relative_path or path.name)
    graph = build_relation_graph(relative_path or path.name, related)

    return {
        "author": primary_author,
        "coauthors": unique_coauthors,
        "contributors": unique_coauthors,
        "date": date_metadata["date"],
        "created_at": date_metadata["created_at"],
        "modified_at": date_metadata["modified_at"],
        "published_at": date_metadata["published_at"],
        "language": date_metadata["language"],
        "source_origin": "source-links/docs",
        "source_path": str(path),
        "related_docs": related,
        "relation_graph": graph,
    }


def infer_tags(path: Path, text: str) -> List[str]:
    path_lower = path.as_posix().lower()
    title_lower = infer_title(path, text).lower()
    lowered = (path_lower + " " + title_lower + " " + text.lower()).lower()

    heuristic = {
        "documentation": ["documentation", "docs", "doc", "manual", "index", "readme"],
        "api": ["api", "openapi", "swagger", "rest api", "rpc", "endpoint", "schema"],
        "reference": ["reference", "specification", "syntax", "command", "cli", "api reference"],
        "support": ["support", "faq", "help", "troubleshooting", "troubleshoot", "issue"],
        "runbook": ["runbook", "operations", "playbook", "incident", "recovery"],
        "security": ["security", "auth", "authorization", "oauth", "audit", "compliance", "sso"],
        "audit": ["audit", "review", "compliance"],
        "compliance": ["compliance", "policy", "security policy", "governance"],
        "benchmark": ["benchmark", "benchmarks", "performance", "latency", "throughput"],
        "performance": ["performance", "latency", "throughput", "benchmark"],
        "testing": ["testing", "qa", "e2e", "integration test", "benchmark"],
        "architecture": ["architecture", "design", "diagram", "blueprint", "system design"],
        "diagram": ["diagram", "graph", "architecture diagram", "flowchart"],
        "governance": ["governance", "policy", "standards", "review process", "community"],
        "release": ["release", "released", "version", "changelog", "roadmap"],
        "roadmap": ["roadmap", "road map", "milestone", "release plan"],
        "changelog": ["changelog", "release notes", "version history"],
        "community": ["community", "contributing", "governance", "maintainer", "team"],
        "how-to": ["how to", "quickstart", "setup", "installation", "configuration", "getting started"],
        "guide": ["guide", "tutorial", "walkthrough", "how-to", "primer"],
        "demo": ["demo", "example", "sample", "showcase"],
        "docker": ["docker", "container", "compose", "image"],
        "package": ["package", "bundle", "distribution", "zip", "artifact"],
        "compendium": ["compendium", "reference library", "knowledge base", "inventory"],
        "analysis": ["analysis", "assessment", "report", "summary"],
        "research": ["research", "exploration", "study", "investigation"],
    }

    scores: Dict[str, int] = {tag: 0 for tag in heuristic}
    for tag, keywords in heuristic.items():
        for keyword in keywords:
            if keyword in lowered:
                scores[tag] += 2 if len(keyword) <= 4 else 1
            if any(token in path_lower or token in title_lower for token in (keyword,)):
                scores[tag] += 1

    # Strong signals from direct path/name matches
    for tag, keywords in heuristic.items():
        for keyword in keywords:
            if keyword in path_lower or keyword in title_lower:
                scores[tag] += 2

    ranked = [tag for tag, score in sorted(scores.items(), key=lambda item: (-item[1], item[0])) if score > 0]
    if not ranked:
        ranked = ["documentation"]
    return normalize_tags(ranked, max_tags=5)


def iter_documents(root: Path) -> Iterable[Path]:
    if not root.exists():
        return []
    for path in sorted(root.rglob("*")):
        if path.is_dir():
            if path.name in EXCLUDED_DIRS:
                continue
            continue
        if path.name.startswith("."):
            continue
        if path.suffix.lower() not in ALLOWED_EXTENSIONS:
            continue
        yield path


def build_manifest(source_root: Path = SOURCE_ROOT) -> Dict[str, Any]:
    documents: List[Dict[str, Any]] = []
    total_size = 0
    for path in iter_documents(source_root):
        text = read_text(path)
        title = infer_title(path, text)
        kind = infer_kind(path, text)
        tags = infer_tags(path, text)
        rel = path.relative_to(source_root).as_posix()
        metadata = infer_metadata(path, text, relative_path=rel)
        digest = sha256_file(path)
        size = path.stat().st_size
        total_size += size

        documents.append({
            "title": title,
            "slug": slugify(title or rel),
            "kind": kind,
            "tags": tags,
            "metadata": metadata,
            "relative_path": rel,
            "source_path": str(path),
            "extension": path.suffix.lower().lstrip("."),
            "size": size,
            "hash": digest,
            "last_modified": datetime.fromtimestamp(path.stat().st_mtime, timezone.utc).isoformat(),
        })

    return {
        "source_root": str(source_root),
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "total_documents": len(documents),
        "total_size": total_size,
        "documents": documents,
    }


def write_manifest(manifest: Dict[str, Any], output_path: Path = DEFAULT_CACHE_PATH) -> Path:
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
    return output_path


def main() -> None:
    parser = argparse.ArgumentParser(description="Scan source-links/docs and create a normalized cache manifest with SHA-256 hashes")
    parser.add_argument("--root", default=str(SOURCE_ROOT), help="Source root to scan")
    parser.add_argument("--json", action="store_true", help="Print JSON to stdout instead of summary")
    parser.add_argument("--write", action="store_true", help="Persist manifest to wordpress-site-factory/output/docs_cache.json")
    args = parser.parse_args()

    source_root = Path(args.root).resolve()
    manifest = build_manifest(source_root)

    if args.write:
        path = write_manifest(manifest, DEFAULT_CACHE_PATH)
        print(f"[docs-manifest] wrote {path}")

    if args.json or args.write:
        if args.json:
            print(json.dumps(manifest, ensure_ascii=False, indent=2))
        return

    print(f"[docs-manifest] root={source_root}")
    print(f"[docs-manifest] documents={manifest['total_documents']} size={manifest['total_size']}")
    for item in manifest["documents"][:10]:
        print(f"  - {item['slug']} | {item['kind']} | {', '.join(item['tags'])} | {item['hash'][:12]}")
    if manifest["total_documents"] > 10:
        print("  - ...")


if __name__ == "__main__":
    main()
