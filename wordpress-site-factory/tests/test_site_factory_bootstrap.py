from __future__ import annotations

import os
import sys
import unittest
from pathlib import Path
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

import build_site
import pages


class SiteFactoryBootstrapTests(unittest.TestCase):
    def test_load_config_applies_environment_overrides(self):
        original = {
            "WP_BASE_URL": os.environ.get("WP_BASE_URL"),
            "WP_USER": os.environ.get("WP_USER"),
            "WP_APP_PASSWORD": os.environ.get("WP_APP_PASSWORD"),
            "WP_USE_AUTH": os.environ.get("WP_USE_AUTH"),
            "WP_THEME": os.environ.get("WP_THEME"),
        }
        try:
            os.environ["WP_BASE_URL"] = "https://example.test"
            os.environ["WP_USER"] = "site-admin"
            os.environ["WP_APP_PASSWORD"] = "secret-token"
            os.environ["WP_USE_AUTH"] = "true"
            os.environ["WP_THEME"] = "themisdb-pulse"

            config = build_site.load_config()
            self.assertEqual(config["wordpress"]["base_url"], "https://example.test")
            self.assertEqual(config["wordpress"]["user"], "site-admin")
            self.assertEqual(config["wordpress"]["app_password"], "secret-token")
            self.assertIs(config["wordpress"]["use_auth"], True)
            self.assertEqual(config["theme"]["stylesheet"], "themisdb-pulse")
        finally:
            for key, value in original.items():
                if value is None:
                    os.environ.pop(key, None)
                else:
                    os.environ[key] = value

    def test_ensure_page_includes_template_and_parent_when_present(self):
        page_data = {
            "title": "Getting Started",
            "slug": "getting-started",
            "status": "publish",
            "template": "page",
            "parent": "documentation",
            "content": "Body",
        }

        with patch.object(pages, "find_page_by_slug", side_effect=[None, {"id": 7}]), \
             patch.object(pages, "rest_request", return_value={"id": 99}) as mock_rest:
            pages.ensure_page("https://example.test", "user", "secret", page_data)

        payload = mock_rest.call_args.kwargs["payload"]
        self.assertEqual(payload["template"], "page")
        self.assertEqual(payload["parent"], 7)


if __name__ == "__main__":
    unittest.main()
