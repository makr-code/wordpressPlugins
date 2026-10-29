from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
from datetime import datetime, timezone
from functools import lru_cache
from pathlib import Path
from typing import Any, Dict, Iterable, List, Tuple

ROOT = Path(__file__).resolve().parents[1]
DEFAULT_DOCS_ROOT = ROOT.parent / "source-links" / "docs"
PUBLIC_DOCS_ROOT = DEFAULT_DOCS_ROOT / "website"
# Default to the full documentation corpus so the importer includes the complete
# source tree, not just the public landing-page subset under /website.
SOURCE_ROOT = DEFAULT_DOCS_ROOT
OUTPUT_DIR = ROOT / "output"
DEFAULT_CACHE_PATH = OUTPUT_DIR / "docs_cache.json"
TAXONOMY_PATH = ROOT / "config" / "taxonomy.json"

EXCLUDED_DIRS = {"_generated", "_standards", "ARCHIVED", ".git"}
ALLOWED_EXTENSIONS = {".md", ".markdown", ".txt", ".html", ".htm", ".json", ".yaml", ".yml"}

TAG_RULES = [
    ("documentation", ["doc", "docs", "guide", "readme", "documentation", "manual", "index"]),
    ("architecture", ["architecture", "design", "system", "graph", "diagram", "whitepaper", "local ai", "local-ai", "air-gap", "air gap", "hybrid cloud", "on-premise", "on premise", "self-hosted", "private ai", "edge deployment"]),
    ("api", ["api", "openapi", "rest", "rpc", "transport"]),
    ("release", ["release", "version", "changelog", "roadmap"]),
    ("support", ["support", "troubleshooting", "faq", "help", "runbook", "ddil", "mission critical", "incident response", "operations", "break-glass"]),
    ("security", ["security", "auth", "audit", "compliance", "kritis", "air-gap", "air gap", "datensouveränität", "datensouveranitat", "sovereignty", "resilience", "local ai", "zero trust", "data sovereignty", "on-premise", "isolated environment"]),
    ("benchmark", ["benchmark", "performance", "testing", "metrics", "latency", "throughput"]),
    ("community", ["community", "governance", "contributing"]),
    ("how-to", ["quickstart", "setup", "installation", "configuration"]),
    ("compliance", ["compliance", "policy", "governance", "security policy", "sovereignty", "datensouveränität", "air-gap", "data sovereignty", "standards", "audit", "control framework"]),
    ("governance", ["governance", "policy", "risk", "standards", "security baseline", "control framework", "data protection", "regulatory", "assurance"]),
]

GLOBAL_TAXONOMY_HINTS = {
    "security": ["kritis", "air-gap", "air gap", "airgap", "ddil", "resilience", "resilient", "secure by design", "local ai", "local-ai", "datensouveränität", "datensouveranitat", "sovereignty", "sovereign", "zero trust", "zero-trust", "data sovereignty", "isolated environment", "offline first", "air gapped"],
    "compliance": ["datensouveränität", "datensouveranitat", "sovereignty", "sovereign", "policy", "governance", "compliance", "kritis", "audit", "data sovereignty", "regulatory", "control framework", "security baseline"],
    "architecture": ["air-gap", "air gap", "airgap", "local ai", "local-ai", "hybrid cloud", "edge deployment", "on-premise", "on premise", "private ai", "self-hosted", "self hosted", "offline-first", "isolated deployment", "air gapped", "infrastructure sovereignty"],
    "support": ["ddil", "mission critical", "runbook", "incident response", "operations", "break-glass", "command center", "operational resilience"],
    "benchmark": ["benchmark", "latency", "throughput", "performance", "response time", "load profile", "throughput benchmark"],
}

TAXONOMY_ALIASES = {
    "security": ["security", "secure", "kritis", "air-gap", "air gap", "airgap", "ddil", "resilience", "resilient", "sovereignty", "sovereign", "datensouveränität", "datensouveranitat", "zero trust", "zero-trust", "local ai", "local-ai", "data sovereignty", "air gapped", "isolated environment"],
    "architecture": ["architecture", "air-gap", "air gap", "airgap", "local ai", "local-ai", "hybrid cloud", "edge deployment", "on-premise", "on premise", "private ai", "self-hosted", "self hosted", "offline first", "air gapped", "isolated deployment", "infrastructure sovereignty"],
    "compliance": ["compliance", "policy", "governance", "kritis", "datensouveränität", "datensouveranitat", "sovereignty", "audit", "standards", "data sovereignty", "control framework", "regulatory", "security baseline"],
    "support": ["support", "runbook", "ddil", "mission critical", "incident response", "operations", "break-glass", "operational resilience"],
    "benchmark": ["benchmark", "latency", "throughput", "performance", "response time", "load profile"],
    "recommended": ["recommended", "editor pick", "editor\'s pick", "lesenswert", "featured", "frontpage feature", "frontpage-featured"],
    "lesenswert": ["lesenswert", "recommended", "editor pick", "editor's pick", "must read", "featured"],
}

SOURCE_CLASSIFIER_HINTS = {
    "internal": ["_generated", "_standards", "archived", "internal-only", "internal docs", "admin-only", "confidential", "restricted"],
    "feature_teaser": ["features.md", "feature catalog", "feature overview", "feature set", "features overview", "module capabilities", "module feature", "module features", "feature matrix", "technical teaser", "capability overview", "customer feature"],
    "developer": ["/src/", "/research/", "/examples/", "roadmap.md", "architecture.md", "audit.md", "security.md", "future_enhancements", "performance_expectations", "doxygen", "@brief", "@file", "phase 1", "phase 2", "gate-", "release status"],
    "public": ["/website/", "/pages/", "landing page", "product overview", "enterprise", "developer", "documentation"],
}


def classify_document_source(path: Path, text: str = "") -> str:
    lowered_path = path.as_posix().lower()
    lowered_text = (text or "").lower()
    combined = f"{lowered_path} {lowered_text}"

    public_root_markers = (
        "source-links/docs/website",
        "/website/",
        "\\website\\",
        "/pages/",
        "\\pages\\",
        "website/pages",
    )
    developer_root_markers = (
        "source-links/src",
        "\\src\\",
        "/src/",
        "source-links/research",
        "/research/",
        "source-links/examples",
        "/examples/",
    )

    if any(marker in lowered_path for marker in public_root_markers):
        return "public"
    if any(hint in lowered_path for hint in SOURCE_CLASSIFIER_HINTS["feature_teaser"]) or any(hint in lowered_path for hint in ("features.md", "featurecatalog", "feature-overview", "feature overview")):
        return "feature-teaser"
    if any(marker in lowered_path for marker in developer_root_markers):
        return "developer"

    if any(hint in lowered_path for hint in SOURCE_CLASSIFIER_HINTS["internal"]):
        return "internal"
    if any(hint in lowered_text for hint in ("confidential", "internal-only", "restricted")) and not any(marker in lowered_path for marker in public_root_markers):
        return "internal"

    if any(hint in combined for hint in SOURCE_CLASSIFIER_HINTS["feature_teaser"]):
        return "feature-teaser"
    if any(hint in combined for hint in SOURCE_CLASSIFIER_HINTS["developer"]):
        return "developer"
    if any(hint in combined for hint in SOURCE_CLASSIFIER_HINTS["public"]):
        return "public"

    return "public"


def build_source_pipeline(path: Path, text: str = "") -> Dict[str, str]:
    source_class = classify_document_source(path, text)
    if source_class == "internal":
        return {"source_class": "internal", "audience": "internal", "pipeline": "internal-excluded"}
    if source_class == "feature-teaser":
        return {"source_class": "feature-teaser", "audience": "customer-technical", "pipeline": "feature-teaser"}
    if source_class == "developer":
        return {"source_class": "developer", "audience": "developer", "pipeline": "developer-docs"}
    return {"source_class": "public", "audience": "public", "pipeline": "public-website"}


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
    raw_items: List[str] = []
    if isinstance(tags, str):
        raw_items = [tags]
    elif isinstance(tags, dict):
        raw_items = [str(value) for value in tags.values()]
    else:
        raw_items = list(tags)

    for tag in raw_items:
        raw = str(tag).strip()
        if not raw:
            continue
        for part in re.split(r"[,;\n]+", raw):
            value = part.strip().lower().replace("_", "-")
            if value.startswith("tag:"):
                value = value.split("tag:", 1)[1].strip()
            if value.startswith("tags:"):
                value = value.split("tags:", 1)[1].strip()
            if not value or value in seen:
                continue
            if value in allowed:
                dedup.append(value)
                seen.add(value)
    if not dedup:
        dedup = ["documentation"]
    return dedup[:max_tags]


def normalize_taxonomy_text(value: str) -> str:
    text = str(value or "").lower()
    replacements = {
        "ä": "ae",
        "ö": "oe",
        "ü": "ue",
        "ß": "ss",
        "-": " ",
        "_": " ",
        "/": " ",
        "\\": " ",
    }
    for old, new in replacements.items():
        text = text.replace(old, new)
    text = re.sub(r"[^a-z0-9\s]+", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    return text


@lru_cache(maxsize=1)
def build_global_taxonomy_index(source_root: str = str(SOURCE_ROOT)) -> Dict[str, int]:
    root = Path(source_root)
    counts: Dict[str, int] = {}
    if not root.exists():
        return counts
    for path in iter_documents(root):
        text = read_text(path)
        if not text:
            continue
        full_text = normalize_taxonomy_text(infer_title(path, text) + "\n" + text)
        for category, hints in GLOBAL_TAXONOMY_HINTS.items():
            for hint in hints:
                phrase = normalize_taxonomy_text(hint)
                if not phrase:
                    continue
                if re.search(rf"(?<![a-z0-9]){re.escape(phrase)}(?![a-z0-9])", full_text):
                    counts[str(category)] = counts.get(str(category), 0) + 1
                    counts[f"{category}:{phrase}"] = counts.get(f"{category}:{phrase}", 0) + 1
    return counts


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
    current_key: str | None = None
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
                    try:
                        parsed = json.loads(value)
                        if isinstance(parsed, list):
                            parsed_tags = [str(item).strip() for item in parsed if str(item).strip()]
                        else:
                            parsed_tags = [part.strip().strip("\"'") for part in inner.split(",") if part.strip()]
                    except Exception:
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
                try:
                    parsed = json.loads(value)
                    if isinstance(parsed, list):
                        items = [str(item).strip() for item in parsed if str(item).strip()]
                    else:
                        items = [part.strip().strip("\"'") for part in inner.split(",") if part.strip()]
                except Exception:
                    items = [part.strip().strip("\"'") for part in inner.split(",") if part.strip()]
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
    slug = metadata.get("slug") or slugify(title)
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
        "anthropic": "Anthropic",
        "copilot": "Copilot",
        "copilot-swe-agent": "Copilot",
        "openai": "OpenAI",
        "gpt-4o": "GPT-4o",
        "gpt4o": "GPT-4o",
        "llama": "Llama",
        "llama3": "Llama 3",
        "meta-llama-3": "Llama 3",
        "mistral": "Mistral",
        "mistral-large": "Mistral Large",
        "deepseek": "DeepSeek",
        "deepseek-r1": "DeepSeek R1",
        "stable diffusion": "Stable Diffusion",
        "stable-diffusion": "Stable Diffusion",
        "midjourney": "Midjourney",
        "dalle": "DALL-E",
        "dall-e": "DALL-E",
        "flux": "Flux",
        "blackforest-flux": "Flux",
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
        "anthropic": "Anthropic",
        "copilot": "Copilot",
        "openai": "OpenAI",
        "gpt-4o": "GPT-4o",
        "gpt4o": "GPT-4o",
        "llama": "Llama",
        "llama3": "Llama 3",
        "meta-llama-3": "Llama 3",
        "mistral": "Mistral",
        "mistral-large": "Mistral Large",
        "deepseek": "DeepSeek",
        "deepseek-r1": "DeepSeek R1",
        "stable diffusion": "Stable Diffusion",
        "stable-diffusion": "Stable Diffusion",
        "midjourney": "Midjourney",
        "dalle": "DALL-E",
        "dall-e": "DALL-E",
        "flux": "Flux",
        "blackforest-flux": "Flux",
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


def validate_frontmatter_metadata(text: str) -> Dict[str, Any]:
    parsed = parse_frontmatter(text)
    required = ["title", "slug", "hash"]
    missing = [key for key in required if not parsed.get(key)]
    issues: List[str] = []
    tags = [str(tag).strip().lower() for tag in (parsed.get("tags") or parsed.get("tag") or []) if str(tag).strip()]
    if isinstance(parsed.get("tag"), str):
        tags.extend(str(parsed.get("tag")).split(","))
    if "title" not in parsed or not str(parsed.get("title", "")).strip():
        issues.append("missing_title")
    if "slug" not in parsed or not str(parsed.get("slug", "")).strip():
        issues.append("missing_slug")
    if "hash" not in parsed or not str(parsed.get("hash", "")).strip():
        issues.append("missing_hash")
    if "research" in tags and (str(parsed.get("status") or "").lower() in {"publish", "published", "reviewed"} or str(parsed.get("review_status") or "").lower() in {"peer-reviewed", "peer reviewed", "reviewed", "accepted"} or parsed.get("reviewed") is True):
        if not str(parsed.get("doi") or "").strip():
            issues.append("missing_research_doi")
    return {
        "valid": not missing and not issues,
        "missing": missing,
        "issues": sorted(set(issues)),
        "fields": parsed,
    }


def score_document_suitability(path: Path, text: str, title: str, tags: List[str]) -> Dict[str, Any]:
    frontmatter = parse_frontmatter(text)
    score_override = frontmatter.get("score") if isinstance(frontmatter.get("score"), (int, float)) else None
    readiness_override = frontmatter.get("readiness") if isinstance(frontmatter.get("readiness"), str) else None

    cleaned = text
    if cleaned.startswith("---"):
        cleaned = re.sub(r"^---\s*(?:.*?\n)?---\s*(?:\n|$)", "", cleaned, flags=re.DOTALL)
    stripped = cleaned.strip()

    score = 76
    flags: List[str] = []

    if not title or len(title.strip()) < 6:
        score -= 18
        flags.append("missing_title")

    if len(stripped) < 150:
        score -= 22
        flags.append("too_short")

    if re.search(r"\b(TBD|TODO|FIXME|coming soon|placeholder|lorem ipsum|\[draft\]|<.*?>)\b", stripped, flags=re.IGNORECASE):
        score -= 20
        flags.append("placeholder_or_todo")

    headings = len(re.findall(r"^#{1,6}\s+", stripped, flags=re.MULTILINE))
    if headings < 2:
        score -= 10
        flags.append("insufficient_structure")

    if "_standards" in path.as_posix().lower() or "standards" in path.as_posix().lower():
        score -= 18
        flags.append("needs_enhancement")

    frontmatter = parse_frontmatter(text)
    if not frontmatter or not frontmatter.get("title"):
        score -= 6
        flags.append("missing_frontmatter")

    broken_markdown_patterns = [
        r"\[[^\]]+\]\([^\)]+\)\([^\)]+\)",
        r"\!\[[^\]]*\]\(\s*\)",
        r"`\s*\{\{.*?\}\}\s*`",
        r"\b(TBD|TODO|FIXME)\b",
    ]
    if any(re.search(pattern, stripped, flags=re.IGNORECASE) for pattern in broken_markdown_patterns):
        score -= 8
        flags.append("markdown_risk")

    if not tags:
        score -= 8
        flags.append("missing_tags")

    score = max(0, min(100, score))
    if score_override is not None:
        score = max(0, min(100, int(score_override)))

    if readiness_override:
        readiness = str(readiness_override).strip().lower()
        if readiness not in {"ready", "needs_review", "blocked"}:
            readiness = "needs_review"
    elif score >= 80:
        readiness = "ready"
    elif score >= 55:
        readiness = "needs_review"
    else:
        readiness = "blocked"

    if score >= 80:
        recommendation = "import"
    elif score >= 55:
        recommendation = "enhance"
    else:
        recommendation = "skip"

    if "research" in [str(tag).lower() for tag in tags if tag] and (readiness == "ready" or score >= 80):
        if not any(value for value in [frontmatter.get("doi"), frontmatter.get("publication_doi")] if value is not None):
            score = max(0, score - 10)
            flags.append("missing_research_doi")
            readiness = "needs_review" if score >= 55 else "blocked"
    if score >= 80:
        recommendation = "import"
    elif score >= 55:
        recommendation = "enhance"
    else:
        recommendation = "skip"
    return {
        "score": score,
        "readiness": readiness,
        "recommendation": recommendation,
        "review_flags": sorted(set(flags)),
    }


def infer_tags(path: Path, text: str) -> List[str]:
    path_lower = normalize_taxonomy_text(path.as_posix())
    title_lower = normalize_taxonomy_text(infer_title(path, text))
    doc_text = normalize_taxonomy_text(text)
    lowered = f"{path_lower} {title_lower} {doc_text}"
    source_pipeline = build_source_pipeline(path, text)

    heuristic = {
        "documentation": ["documentation", "docs", "doc", "manual", "index", "readme"],
        "api": ["api", "openapi", "swagger", "rest api", "rpc", "endpoint", "schema"],
        "reference": ["reference", "specification", "syntax", "command", "cli", "api reference"],
        "support": ["support", "faq", "help", "troubleshooting", "troubleshoot", "issue", "ddil", "mission critical", "runbook", "incident response", "break glass", "operations"],
        "runbook": ["runbook", "operations", "playbook", "incident", "recovery", "command center"],
        "security": ["security", "auth", "authorization", "oauth", "audit", "compliance", "sso", "kritis", "air gap", "airgap", "datensouveranitat", "datensouveraenitaet", "sovereignty", "resilience", "resilient", "zero trust", "data sovereignty", "local ai", "air gapped", "isolated environment"],
        "audit": ["audit", "review", "compliance", "control framework"],
        "compliance": ["compliance", "policy", "security policy", "governance", "datensouveranitat", "datensouveraenitaet", "sovereignty", "air gap", "kritis", "data sovereignty", "regulatory", "standards", "assurance"],
        "benchmark": ["benchmark", "benchmarks", "performance", "latency", "throughput", "response time", "load profile"],
        "performance": ["performance", "latency", "throughput", "benchmark", "load profile"],
        "testing": ["testing", "qa", "e2e", "integration test", "benchmark", "load profile"],
        "architecture": ["architecture", "design", "diagram", "blueprint", "system design", "local ai", "hybrid cloud", "air gap", "airgap", "on premise", "on-premise", "private ai", "self hosted", "self-hosted", "offline first", "isolated deployment", "edge deployment", "doxygen", "module", "roadmap"],
        "diagram": ["diagram", "graph", "architecture diagram", "flowchart", "system design"],
        "governance": ["governance", "policy", "standards", "review process", "community", "risk", "control framework", "data protection", "regulatory"],
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
        "recommended": ["recommended", "editor pick", "editor's pick", "must read", "featured", "frontpage feature", "frontpage-featured", "lesenswert"],
        "lesenswert": ["lesenswert", "recommended", "editor pick", "editor's pick", "must read", "featured"],
    }

    if source_pipeline["source_class"] in {"developer", "feature-teaser"}:
        heuristic["feature"] = ["feature", "features", "module", "capability", "roadmap", "status", "phase", "integration", "gate", "feature set", "feature catalog", "technical teaser"]
        heuristic["documentation"].extend(["readme", "architecture", "roadmap", "audit", "security", "future enhancements", "feature overview", "feature list", "feature teaser"])
        if path.name.lower() == "features.md":
            heuristic["feature"].extend(["feature", "features", "capability", "module", "module capability", "feature marketing", "technical feature"])

    scores: Dict[str, int] = {tag: 0 for tag in heuristic}
    for tag, keywords in heuristic.items():
        for keyword in keywords:
            normalized_keyword = normalize_taxonomy_text(keyword)
            if not normalized_keyword:
                continue
            if re.search(rf"(?<![a-z0-9]){re.escape(normalized_keyword)}(?![a-z0-9])", lowered):
                scores[tag] += 3 if len(normalized_keyword.split()) <= 2 else 2
            if normalized_keyword in path_lower or normalized_keyword in title_lower:
                scores[tag] += 2

    global_index = build_global_taxonomy_index(str(SOURCE_ROOT))
    for tag, aliases in TAXONOMY_ALIASES.items():
        for alias in aliases:
            phrase = normalize_taxonomy_text(alias)
            if not phrase:
                continue
            if re.search(rf"(?<![a-z0-9]){re.escape(phrase)}(?![a-z0-9])", lowered):
                scores[tag] += max(1, global_index.get(tag, 0) // 25)

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


def infer_document_module(path: Path, text: str = "") -> str:
    raw = (path.as_posix() + " " + str(text or "")).lower().replace("\\", "/")
    if "source-links/docs/website" in raw or "/website/" in raw or "/pages/" in raw:
        return "website"

    path_text = path.as_posix().lower().replace("\\", "/")
    relative = path_text
    if "source-links/docs/" in relative:
        relative = relative.split("source-links/docs/", 1)[1]
    if relative.startswith("./"):
        relative = relative[2:]

    candidates = [segment.lower() for segment in relative.split("/") if segment and not segment.startswith("_")]
    module_aliases = {
        "website": ["website", "pages", "marketing", "landing"],
        "api": ["api", "rest", "openapi", "rpc", "endpoint", "schema"],
        "architecture": ["architecture", "design", "diagram", "roadmap", "adr", "blueprint"],
        "security": ["security", "kritis", "air-gap", "airgap", "ddil", "sovereignty", "compliance"],
        "benchmark": ["benchmark", "performance", "latency", "throughput", "metrics"],
        "support": ["support", "help", "faq", "troubleshooting", "runbook", "operations"],
        "reference": ["reference", "specification", "cli", "docs", "manual"],
        "examples": ["example", "examples", "tutorial", "how-to", "guide", "quickstart"],
        "integrations": ["integration", "integrations", "connector", "adapter", "plugin"],
        "community": ["community", "contributing", "governance", "team"],
        "release": ["release", "releases", "changelog", "version"],
        "research": ["research", "analysis", "study", "insight", "whitepaper", "report"],
    }

    for module_name, keywords in module_aliases.items():
        if any(keyword in raw for keyword in keywords):
            return module_name
        if any(keyword in candidates for keyword in keywords):
            return module_name

    if candidates:
        for segment in candidates:
            if segment not in {"docs", "website", "source-links"}:
                return segment.replace("_", "-")
    return "general"


def group_documents_by_module(manifest: Dict[str, Any]) -> Dict[str, List[Dict[str, Any]]]:
    grouped: Dict[str, List[Dict[str, Any]]] = {}
    for document in manifest.get("documents", []):
        module_name = str(document.get("module") or infer_document_module(Path(document.get("source_path") or document.get("relative_path") or ""), str(document.get("title") or ""))).strip() or "general"
        grouped.setdefault(module_name, []).append(document)
    return grouped


def build_manifest(source_root: Path = SOURCE_ROOT) -> Dict[str, Any]:
    documents: List[Dict[str, Any]] = []
    total_size = 0
    pipeline_counts: Dict[str, int] = {"public": 0, "developer": 0, "feature-teaser": 0, "internal": 0}
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

        pipeline = build_source_pipeline(path, text)
        pipeline_counts[pipeline["source_class"]] = pipeline_counts.get(pipeline["source_class"], 0) + 1

        suitability = score_document_suitability(path, text, title, tags)
        module_name = infer_document_module(path, text)
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
            "source_class": pipeline["source_class"],
            "audience": pipeline["audience"],
            "pipeline": pipeline["pipeline"],
            "module": module_name,
            "suitability": suitability,
        })

    return {
        "source_root": str(source_root),
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "total_documents": len(documents),
        "total_size": total_size,
        "source_class_counts": pipeline_counts,
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
    parser.add_argument("--validate-frontmatter", action="store_true", help="Validate compact YAML frontmatter on source markdown files")
    args = parser.parse_args()

    if args.validate_frontmatter:
        scanned = 0
        issues = []
        for path in iter_documents(Path(args.root).resolve()):
            if path.suffix.lower() not in {".md", ".markdown", ".txt", ".html", ".htm"}:
                continue
            scanned += 1
            text = read_text(path)
            result = validate_frontmatter_metadata(text)
            if not result["valid"]:
                issues.append({
                    "path": str(path),
                    "missing": result["missing"],
                    "issues": result["issues"],
                })
        print(json.dumps({"checked": scanned, "files_with_issues": issues[:50]}, ensure_ascii=False, indent=2))
        raise SystemExit(1 if issues else 0)

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
