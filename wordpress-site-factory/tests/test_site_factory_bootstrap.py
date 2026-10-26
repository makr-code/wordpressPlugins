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

    def test_ensure_page_includes_parent_when_present_and_omits_unknown_template(self):
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
        self.assertNotIn("template", payload)
        self.assertEqual(payload["parent"], 7)

    def test_set_front_page_assigns_home_and_blog_pages(self):
        with patch.object(pages, "find_page_by_slug", side_effect=[{"id": 42}, {"id": 77}]), \
             patch.object(pages, "rest_request", return_value={"ok": True}) as mock_rest:
            pages.set_front_page("https://example.test", "user", "secret", "home")

        payload = mock_rest.call_args.kwargs["payload"]
        self.assertEqual(payload["show_on_front"], "page")
        self.assertEqual(payload["page_on_front"], 42)
        self.assertEqual(payload["page_for_posts"], 77)

    def test_sanitize_shortcode_payload_wraps_raw_shortcode_blocks(self):
        raw = '<p>[themisdb_front_slider posts="5" filter="hero,featured"] [themisdb_v3_blog_section limit="9" category="blog"]</p>'

        sanitized = pages.sanitize_shortcode_payload(raw)

        self.assertIn("<!-- wp:shortcode -->[themisdb_front_slider", sanitized)
        self.assertIn("<!-- wp:shortcode -->[themisdb_v3_blog_section", sanitized)
        self.assertNotIn("<p>[themisdb_front_slider", sanitized)

    def test_sanitize_shortcode_payload_preserves_surrounding_html(self):
        raw = '<h2>Blog</h2><p class="subtitle">[themisdb_front_slider posts="5"] [themisdb_v3_blog_section limit="9" category="blog"]</p><p>Footer</p>'

        sanitized = pages.sanitize_shortcode_payload(raw)

        self.assertIn('<h2>Blog</h2>', sanitized)
        self.assertIn('<!-- wp:shortcode -->[themisdb_front_slider posts="5"]', sanitized)
        self.assertIn('<!-- wp:shortcode -->[themisdb_v3_blog_section limit="9" category="blog"]', sanitized)
        self.assertIn('<p>Footer</p>', sanitized)

    def test_ensure_page_wraps_html_landing_pages_in_gutenberg_html_block(self):
        page_data = {
            "title": "Features",
            "slug": "features",
            "status": "publish",
            "content": '<div class="hero"><h1>Features</h1><p>Detail</p></div>',
        }

        with patch.object(pages, "find_page_by_slug", return_value=None), \
             patch.object(pages, "rest_request", return_value={"id": 11}) as mock_rest:
            pages.ensure_page("https://example.test", "user", "secret", page_data)

        payload = mock_rest.call_args.kwargs["payload"]
        self.assertTrue(payload["content"].startswith("<!-- wp:html -->"))
        self.assertIn("<div class=\"hero\">", payload["content"])
        self.assertIn("<!-- /wp:html -->", payload["content"])

    def test_load_html_page_content_reads_static_landing_pages(self):
        pages_payload = pages.load_html_page_content()
        slugs = {page["slug"] for page in pages_payload}
        self.assertIn("features", slugs)
        self.assertIn("community", slugs)
        self.assertIn("download", slugs)


if __name__ == "__main__":
    unittest.main()
