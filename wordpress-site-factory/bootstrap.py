from __future__ import annotations

import json
import os
import shutil
import subprocess
import sys
from pathlib import Path
from typing import Any, Dict, Optional

import requests

ROOT = Path(__file__).resolve().parent
CONFIG_DIR = ROOT / "config"
DEFAULT_LOCAL_WORDPRESS_ROOT = Path(r"C:\xampp\htdocs\wordpress")


def load_json(path: Path) -> Dict[str, Any]:
    with path.open("r", encoding="utf-8") as handle:
        return json.load(handle)


def build_auth_tuple(user: str, password: str, use_auth: bool = True):
    if not use_auth:
        return None
    if not user or not str(password or "").strip():
        return None
    return (user, str(password))


def validate_wordpress_auth_settings(config: Dict[str, Any], dry_run: bool = False) -> None:
    wordpress_cfg = config.get("wordpress", {})
    use_auth = bool(wordpress_cfg.get("use_auth", False))
    password = str(wordpress_cfg.get("app_password") or "").strip()

    if dry_run:
        if use_auth and not password:
            print("[bootstrap] Dry run: auth is enabled but no app_password is configured; skipping live credential validation.")
        elif not use_auth:
            print("[bootstrap] Dry run: auth is disabled; safe preview without live REST credentials.")
        return

    if not use_auth:
        raise RuntimeError(
            "WordPress auth is disabled. For a live factory run, set wordpress.use_auth to true and configure a valid app_password before calling wp-json/wp/v2/settings."
        )
    if not password:
        raise RuntimeError("WordPress auth is enabled but app_password is empty. Set the password before running the live factory.")


def rest_request(base_url: str, path: str, user: str, password: str, method: str = "GET", payload: Optional[Dict[str, Any]] = None, use_auth: bool = True):
    url = base_url.rstrip("/") + "/" + path.lstrip("/")
    auth = build_auth_tuple(user, password, use_auth)
    response = requests.request(method=method, url=url, auth=auth, json=payload, timeout=20)
    if response.status_code >= 400:
        raise RuntimeError(f"REST request failed: {method} {url} -> {response.status_code}: {response.text[:400]}")
    if response.content:
        try:
            return response.json()
        except ValueError:
            return response.text
    return None


def resolve_plugin_identifier(plugin_reference: str, installed_map: Dict[str, str]) -> str | None:
    if not plugin_reference:
        return None

    if "/" in plugin_reference:
        return plugin_reference

    if plugin_reference in installed_map:
        return plugin_reference

    site_root = DEFAULT_LOCAL_WORDPRESS_ROOT
    plugin_dir = site_root / "wp-content" / "plugins" / plugin_reference
    if plugin_dir.is_dir():
        php_candidates = sorted(path.name for path in plugin_dir.glob("*.php"))
        if not php_candidates:
            return None
        preferred = [f"{plugin_reference}/{plugin_reference}.php", f"{plugin_reference}/{plugin_reference.replace('-', '_')}.php"]
        for candidate in preferred:
            if candidate in installed_map:
                return candidate
        for candidate_name in php_candidates:
            candidate = f"{plugin_reference}/{candidate_name}"
            if candidate in installed_map:
                return candidate
        return f"{plugin_reference}/{php_candidates[0]}"

    return None


def ensure_site_ready(config: Dict[str, Any], dry_run: bool = False) -> Dict[str, Any]:
    wordpress_cfg = config["wordpress"]
    base_url = wordpress_cfg["base_url"]
    user = wordpress_cfg["user"]
    password = wordpress_cfg["app_password"]
    use_auth = bool(wordpress_cfg.get("use_auth", False))

    print(f"[bootstrap] Checking WordPress connection at {base_url}")
    if dry_run:
        validate_wordpress_auth_settings(config, dry_run=True)
        print("[bootstrap] Dry run: connection check only")
        return {"status": "dry-run", "base_url": base_url}

    validate_wordpress_auth_settings(config, dry_run=False)

    try:
        settings = rest_request(base_url, "wp-json/wp/v2/settings", user, password, use_auth=use_auth)
        site_title = settings.get("title", "") if isinstance(settings, dict) else ""
        print(f"[bootstrap] Site reachable: {site_title or 'WordPress'}")
    except Exception as exc:  # pragma: no cover - network fallback
        raise RuntimeError(f"WordPress REST API unavailable: {exc}") from exc

    theme_cfg = config["theme"]
    themes = rest_request(base_url, "wp-json/wp/v2/themes", user, password, use_auth=use_auth)
    theme_names = [theme.get("stylesheet") for theme in themes if isinstance(theme, dict)]
    if theme_cfg["stylesheet"] not in theme_names:
        print(f"[bootstrap] Theme '{theme_cfg['stylesheet']}' is not currently installed. Check theme install step before applying full site setup.")
    else:
        print(f"[bootstrap] Theme '{theme_cfg['stylesheet']}' is available")

    plugins = config.get("plugins", [])
    installed_plugins = rest_request(base_url, "wp-json/wp/v2/plugins", user, password, use_auth=use_auth)
    installed_map = {}
    for plugin in installed_plugins if isinstance(installed_plugins, list) else []:
        if isinstance(plugin, dict):
            installed_map[plugin.get("plugin")] = plugin.get("status")

    for plugin in plugins:
        resolved = resolve_plugin_identifier(plugin, installed_map)
        if resolved is None:
            print(f"[bootstrap] Plugin '{plugin}' is not installed or not discoverable on disk yet")
            continue

        status = installed_map.get(resolved)
        if status is None:
            print(f"[bootstrap] Plugin '{resolved}' is on disk but not registered with WordPress yet")
            continue

        if status == "active":
            print(f"[bootstrap] Plugin '{resolved}' is installed and active")
            continue

        print(f"[bootstrap] Activating plugin '{resolved}' from status '{status}'")
        activation = rest_request(base_url, f"wp-json/wp/v2/plugins/{resolved}", user, password, method="PUT", payload={"status": "active"}, use_auth=use_auth)
        print(f"[bootstrap] Activation result: {activation}")

    return {"status": "ok", "base_url": base_url, "theme": theme_cfg["stylesheet"]}


def resolve_cli_executable(candidates: list[str]) -> str:
    for candidate in candidates:
        resolved = shutil.which(candidate)
        if resolved:
            return resolved
    for candidate in candidates:
        if Path(candidate).exists():
            return str(Path(candidate))
    return candidates[0]


def ensure_ai_coauthors(config: Dict[str, Any], dry_run: bool = False) -> Dict[str, Any]:
    bootstrap_cfg = config.get("bootstrap", {})
    if not bootstrap_cfg.get("ai_coauthors", True):
        return {"status": "skipped", "reason": "ai_coauthors disabled"}

    repo_root = ROOT.parent
    setup_script = repo_root / "themisdb-pulse" / "tools" / "setup-ai-coauthors.php"
    avatar_script = repo_root / "page-content-blog" / "upload_avatars.py"

    if dry_run:
        print("[bootstrap] Dry run: would create the AI guest-author roster and attach avatar assets")
        return {"status": "dry-run", "script": str(setup_script), "avatars": str(avatar_script)}

    if not setup_script.exists():
        raise FileNotFoundError(f"AI guest-author bootstrap script not found: {setup_script}")

    php_executable = resolve_cli_executable(["php", "php.exe", r"C:\xampp\php\php.exe"])
    print(f"[bootstrap] Ensuring AI guest-author roster via {setup_script} using {php_executable}")
    setup_result = subprocess.run([php_executable, str(setup_script)], cwd=str(repo_root), capture_output=True, text=True)
    if setup_result.returncode != 0:
        detail = setup_result.stderr.strip() or setup_result.stdout.strip() or "unknown error"
        raise RuntimeError(f"AI guest-author bootstrap failed: {detail}")
    if setup_result.stdout.strip():
        print(setup_result.stdout.strip())

    if avatar_script.exists() and bootstrap_cfg.get("upload_avatars", True):
        env = os.environ.copy()
        wordpress_cfg = config["wordpress"]
        env["WP_BASE_URL"] = wordpress_cfg["base_url"]
        env["WP_REST_ROUTE_STYLE"] = wordpress_cfg.get("rest_route_style", "wp-json")
        env["WP_USER"] = wordpress_cfg["user"]
        env["WP_APP_PASSWORD"] = wordpress_cfg["app_password"]

        python_executable = resolve_cli_executable(["python", "python.exe", sys.executable])
        print(f"[bootstrap] Uploading AI-author avatars via {avatar_script} using {python_executable}")
        avatar_result = subprocess.run([python_executable, str(avatar_script), "--apply"], cwd=str(repo_root), env=env, capture_output=True, text=True)
        if avatar_result.returncode != 0:
            detail = avatar_result.stderr.strip() or avatar_result.stdout.strip() or "unknown error"
            raise RuntimeError(f"AI avatar upload failed: {detail}")
        if avatar_result.stdout.strip():
            print(avatar_result.stdout.strip())

    return {"status": "ok", "setup_script": str(setup_script), "avatar_script": str(avatar_script)}
