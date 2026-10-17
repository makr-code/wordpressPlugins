from __future__ import annotations

import json
from pathlib import Path
from typing import Any, Dict, Optional

import requests

ROOT = Path(__file__).resolve().parent
CONFIG_DIR = ROOT / "config"


def load_json(path: Path) -> Dict[str, Any]:
    with path.open("r", encoding="utf-8") as handle:
        return json.load(handle)


def rest_request(base_url: str, path: str, user: str, password: str, method: str = "GET", payload: Optional[Dict[str, Any]] = None):
    url = base_url.rstrip("/") + "/" + path.lstrip("/")
    auth = (user, password)
    response = requests.request(method=method, url=url, auth=auth, json=payload, timeout=20)
    if response.status_code >= 400:
        raise RuntimeError(f"REST request failed: {method} {url} -> {response.status_code}: {response.text[:400]}")
    if response.content:
        try:
            return response.json()
        except ValueError:
            return response.text
    return None


def ensure_site_ready(config: Dict[str, Any], dry_run: bool = False) -> Dict[str, Any]:
    wordpress_cfg = config["wordpress"]
    base_url = wordpress_cfg["base_url"]
    user = wordpress_cfg["user"]
    password = wordpress_cfg["app_password"]

    print(f"[bootstrap] Checking WordPress connection at {base_url}")
    if dry_run:
        print("[bootstrap] Dry run: connection check only")
        return {"status": "dry-run", "base_url": base_url}

    try:
        settings = rest_request(base_url, "wp-json/wp/v2/settings", user, password)
        site_title = settings.get("title", "") if isinstance(settings, dict) else ""
        print(f"[bootstrap] Site reachable: {site_title or 'WordPress'}")
    except Exception as exc:  # pragma: no cover - network fallback
        raise RuntimeError(f"WordPress REST API unavailable: {exc}") from exc

    theme_cfg = config["theme"]
    themes = rest_request(base_url, "wp-json/wp/v2/themes", user, password)
    theme_names = [theme.get("stylesheet") for theme in themes if isinstance(theme, dict)]
    if theme_cfg["stylesheet"] not in theme_names:
        print(f"[bootstrap] Theme '{theme_cfg['stylesheet']}' is not currently installed. Check theme install step before applying full site setup.")
    else:
        print(f"[bootstrap] Theme '{theme_cfg['stylesheet']}' is available")

    plugins = config.get("plugins", [])
    installed_plugins = rest_request(base_url, "wp-json/wp/v2/plugins", user, password)
    installed_map = {}
    for plugin in installed_plugins if isinstance(installed_plugins, list) else []:
        if isinstance(plugin, dict):
            installed_map[plugin.get("plugin")] = plugin.get("status")

    for plugin in plugins:
        if plugin in installed_map:
            print(f"[bootstrap] Plugin '{plugin}' is installed with status '{installed_map[plugin]}'")
        else:
            print(f"[bootstrap] Plugin '{plugin}' is not installed yet")

    return {"status": "ok", "base_url": base_url, "theme": theme_cfg["stylesheet"]}
