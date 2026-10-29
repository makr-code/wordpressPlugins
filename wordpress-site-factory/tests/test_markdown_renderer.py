from __future__ import annotations

import importlib.util
from pathlib import Path

FIXTURE_DIR = Path(__file__).parent / "fixtures" / "markdown_rendering"
DOCS_IMPORT_PATH = Path(__file__).resolve().parents[1] / "importers" / "docs_import.py"
DOCS_MANIFEST_PATH = Path(__file__).resolve().parents[1] / "importers" / "docs_manifest.py"

spec = importlib.util.spec_from_file_location("docs_import", DOCS_IMPORT_PATH)
docs_import = importlib.util.module_from_spec(spec)
assert spec is not None and spec.loader is not None
spec.loader.exec_module(docs_import)

manifest_spec = importlib.util.spec_from_file_location("docs_manifest", DOCS_MANIFEST_PATH)
docs_manifest = importlib.util.module_from_spec(manifest_spec)
assert manifest_spec is not None and manifest_spec.loader is not None
manifest_spec.loader.exec_module(docs_manifest)


def load_fixture(name: str) -> str:
    return (FIXTURE_DIR / name).read_text(encoding="utf-8")


def test_missing_site_config_is_nonfatal_and_uses_safe_defaults(monkeypatch, tmp_path):
    monkeypatch.setattr(docs_import, "SITE_CONFIG_PATH", tmp_path / "missing-site.json")
    assert docs_import.load_site_config(required=False) == {}
    assert docs_import.get_runtime_site_setting("base_url", "http://localhost/wordpress") == "http://localhost/wordpress"
    assert docs_import.get_runtime_site_setting("rest_route_style", "wp-json") == "wp-json"


def test_default_manifest_root_scans_full_docs_tree():
    assert docs_manifest.DEFAULT_DOCS_ROOT.exists()
    assert docs_manifest.SOURCE_ROOT == docs_manifest.DEFAULT_DOCS_ROOT


def test_repair_mojibake_text_recovers_common_utf8_double_encoding():
    raw = "Das ist ein Ã¼berwachter Text mit â€“ und Ã¤hnlichen Zeichen."
    repaired = docs_import.repair_mojibake_text(raw)
    assert "überwachter" in repaired.lower()
    assert "–" in repaired
    assert "ähnlichen" in repaired.lower()


def test_mojibake_signature_detects_corrupted_source_text():
    assert docs_import.has_mojibake_signature("Die Ã¼berwachung ist kritisch.")
    assert docs_import.has_mojibake_signature("Das ist ein �berwachter Text.")
    assert not docs_import.has_mojibake_signature("Die Überwachung ist kritisch.")


def test_good_markdown_renders_structured_html():
    html = docs_import.markdown_to_html(
        load_fixture("good.md"),
        current_relative_path="pages/good.md",
        doc_lookup={"about.md": "about-page", "docs/architecture.md": "architecture-page"},
        base_url="http://localhost/wordpress",
    )
    assert "<table>" in html
    assert "<h1>" in html
    assert "<pre><code" in html
    assert "<blockquote>" in html
    assert "<!--" not in html
    assert "http://localhost/wordpress/about-page" in html


def test_mixed_markdown_removes_comments_and_keeps_table():
    html = docs_import.markdown_to_html(
        load_fixture("mixed.md"),
        current_relative_path="pages/mixed.md",
        doc_lookup={"about.md": "about-page"},
        base_url="http://localhost/wordpress",
    )
    assert "<table>" in html
    assert "<ul>" in html
    assert "<!--" not in html
    assert "http://localhost/wordpress/about-page" in html


def test_table_edge_case_renders_without_crashing():
    html = docs_import.markdown_to_html(
        load_fixture("edge_table.md"),
        current_relative_path="pages/edge_table.md",
        doc_lookup={"about.md": "about-page"},
        base_url="http://localhost/wordpress",
    )
    assert "<table>" in html
    assert "<th" in html
    assert "<!--" not in html
    assert "http://localhost/wordpress/about-page" in html


def test_weird_markdown_does_not_crash_and_strips_comments():
    html = docs_import.markdown_to_html(
        load_fixture("weird.md"),
        current_relative_path="pages/weird.md",
        doc_lookup={"missing.md": "missing-page"},
        base_url="http://localhost/wordpress",
    )
    assert html.startswith("<div")
    assert "<!--" not in html
    assert "<ul>" in html or "<p>" in html


def test_poor_markdown_still_renders_and_is_safe():
    html = docs_import.markdown_to_html(
        load_fixture("poor.md"),
        current_relative_path="pages/poor.md",
        doc_lookup={},
        base_url="http://localhost/wordpress",
    )
    assert html.startswith("<div")
    assert "TODO" in html or "placeholder" in html or "draft" in html
    assert "<!--" not in html


def test_mermaid_fence_is_rendered_for_live_diagrams():
    html = docs_import.markdown_to_html(
        load_fixture("mermaid.md"),
        current_relative_path="pages/mermaid.md",
        doc_lookup={},
        base_url="http://localhost/wordpress",
    )
    assert '<pre class="mermaid"' in html
    assert "flowchart TD" in html
    assert "mermaid.initialize" in html


def test_mermaid_block_renders_with_live_client_side_class():
    html = docs_import.markdown_to_html(
        "```mermaid\nflowchart TD\nA-->B\n```\n",
        current_relative_path="pages/mermaid_live.md",
        doc_lookup={},
        base_url="http://localhost/wordpress",
    )
    assert '<pre class="mermaid"' in html
    assert 'mermaid.initialize' in html


def test_build_blog_media_html_embeds_video_audio_and_document_players(monkeypatch, tmp_path):
    article_dir = tmp_path / "article"
    article_dir.mkdir()
    article_file = article_dir / "article.md"
    article_file.write_text("# Demo\n\nContent\n", encoding="utf-8")
    (article_dir / "video-demo.mp4").write_bytes(b"video")
    (article_dir / "audio-demo.mp3").write_bytes(b"audio")
    (article_dir / "spec-sheet.pdf").write_bytes(b"pdf")
    (article_dir / "diagram.svg").write_bytes(b'<svg xmlns="http://www.w3.org/2000/svg"></svg>')

    def fake_upload(base_url, user, password, local_path, title, alt_text):
        return {"id": 42, "url": f"https://example.test/{Path(local_path).name}"}

    monkeypatch.setattr(docs_import, "upload_media_asset_info", fake_upload)

    result = docs_import.build_blog_media_html(
        {"source_path": str(article_file)},
        "http://localhost/wordpress",
        "user",
        "pass",
        dry_run=False,
    )

    assert 'themisdb-import-video-player' in result["html"]
    assert 'themisdb-import-audio-player' in result["html"]
    assert 'themisdb-import-document' in result["html"]
    assert '<video' in result["html"]
    assert '<audio' in result["html"]
    assert '<a href="https://example.test/spec-sheet.pdf"' in result["html"]


def test_nested_lists_render_without_breaking():
    html = docs_import.markdown_to_html(
        load_fixture("nested_lists.md"),
        current_relative_path="pages/nested_lists.md",
        doc_lookup={"about.md": "about-page"},
        base_url="http://localhost/wordpress",
    )
    assert "<ol>" in html
    assert "First sub-item" in html
    assert "Paragraph after the list" in html
    assert "http://localhost/wordpress/about-page" in html


def test_malformed_links_do_not_crash_rendering():
    html = docs_import.markdown_to_html(
        load_fixture("malformed_links.md"),
        current_relative_path="pages/malformed_links.md",
        doc_lookup={"about.md": "about-page"},
        base_url="http://localhost/wordpress",
    )
    assert html.startswith("<div")
    assert "http://localhost/wordpress/about-page" in html
    assert "<!--" not in html


def test_html_embed_is_left_intact():
    html = docs_import.markdown_to_html(
        load_fixture("html_embed.md"),
        current_relative_path="pages/html_embed.md",
        doc_lookup={},
        base_url="http://localhost/wordpress",
    )
    assert "<div class=\"note\">" in html
    assert "<strong>Important:" in html
    assert "<blockquote>" in html


def test_target_block_contract_normalizes_core_blocks():
    blocks = docs_import.normalize_markdown_to_target_blocks(
        load_fixture("good.md"),
        current_relative_path="pages/good.md",
        doc_lookup={"about.md": "about-page"},
        base_url="http://localhost/wordpress",
    )
    types = [block["type"] for block in blocks]
    assert "heading" in types
    assert "paragraph" in types
    assert "list" in types
    assert "table" in types
    assert "code" in types
    assert "blockquote" in types
    assert docs_import.validate_target_blocks(blocks) == []


def test_callout_blocks_are_normalized_and_rendered():
    blocks = docs_import.normalize_markdown_to_target_blocks(
        load_fixture("callout.md"),
        current_relative_path="pages/callout.md",
        doc_lookup={},
        base_url="http://localhost/wordpress",
    )
    types = [block["type"] for block in blocks]
    assert "callout" in types
    assert docs_import.validate_target_blocks(blocks) == []
    html = docs_import.render_target_blocks_to_html(blocks, current_relative_path="pages/callout.md", base_url="http://localhost/wordpress")
    assert "callout-warning" in html or "callout" in html
    assert "destructive" in html


def test_markdown_standard_features_render_cleanly():
    html = docs_import.markdown_to_html(
        load_fixture("standards_check.md"),
        current_relative_path="pages/standards_check.md",
        doc_lookup={"about.md": "about-page"},
        base_url="http://localhost/wordpress",
    )
    assert "<h1>" in html
    assert "<h2>" in html
    assert "<table>" in html
    assert "<blockquote>" in html
    assert "<pre><code" in html
    assert "<input type=\"checkbox\"" in html
    assert "http://localhost/wordpress/about-page" in html
    assert "<!--" not in html


def test_markdown_advanced_standard_features_render_cleanly():
    html = docs_import.markdown_to_html(
        load_fixture("advanced_standard_check.md"),
        current_relative_path="pages/advanced_standard_check.md",
        doc_lookup={"about.md": "about-page"},
        base_url="http://localhost/wordpress",
    )
    assert "<h1>" in html
    assert "<h2>" in html
    assert "<table>" in html
    assert "<blockquote>" in html
    assert "<pre><code" in html
    assert "<div class=\"note\">" in html
    assert "<input type=\"checkbox\"" in html
    assert "http://localhost/wordpress/about-page" in html
    assert "<!--" not in html


def test_markdown_table_without_leading_pipes_renders_as_html_table():
    html = docs_import.markdown_to_html(
        "# Example\n\nName | Role\n--- | ---\nAlice | Admin\nBob | Editor\n",
        current_relative_path="pages/table.md",
        doc_lookup={},
        base_url="http://localhost/wordpress",
    )
    assert "<table>" in html
    assert "<th>" in html
    assert "<td>Alice</td>" in html
    assert "<td>Admin</td>" in html


def test_markdown_renderer_preserves_theme_compatible_lead_and_quote_classes():
    html = docs_import.markdown_to_html(
        "# Example\n\nThis is the cover paragraph.\n\n> This is a quote.\n",
        current_relative_path="pages/theme_compat.md",
        doc_lookup={},
        base_url="http://localhost/wordpress",
    )
    assert 'class="tv3-article-lead"' in html
    assert 'class="quote left kernthese"' in html
    assert "This is a quote." in html


def test_duplicate_leading_h1_is_removed_when_it_matches_title():
    html = '<div class="themisdb-formula-content"><h1>Example title</h1><p>Body text</p></div>'
    cleaned = docs_import.strip_duplicate_leading_h1(html, "Example title")
    assert "<h1>Example title</h1>" not in cleaned
    assert "Body text" in cleaned


def test_infer_tags_captures_security_and_compliance_keywords():
    tags = docs_manifest.infer_tags(Path("internal-planning-notes.md"), "# KRITIS-ready Datenplattform\n\nAir-Gap, Datensouveränität, lokale KI made in Germany\n")
    assert "security" in tags or "compliance" in tags
    assert "architecture" in tags or "governance" in tags


def test_infer_tags_captures_local_ai_and_ddil_family_terms():
    tags = docs_manifest.infer_tags(
        Path("mission-critical-airgap.md"),
        "# Local AI Operations\n\nDDIL operations require air-gapped deployment and sovereign data handling for mission critical workloads.\n",
    )
    assert "security" in tags or "support" in tags
    assert "architecture" in tags or "compliance" in tags


def test_classify_document_source_separates_public_and_developer_content():
    public_path = Path("source-links/docs/website/pages/themisdb.md")
    developer_path = Path("source-links/src/access_model/ROADMAP.md")
    feature_path = Path("source-links/src/FEATURES.md")
    internal_path = Path("source-links/docs/_standards/security-policy.md")

    assert docs_manifest.classify_document_source(public_path) == "public"
    assert docs_manifest.classify_document_source(developer_path) == "developer"
    assert docs_manifest.classify_document_source(feature_path) == "feature-teaser"
    assert docs_manifest.classify_document_source(internal_path) == "internal"

    public_pipeline = docs_manifest.build_source_pipeline(public_path)
    developer_pipeline = docs_manifest.build_source_pipeline(developer_path)
    feature_pipeline = docs_manifest.build_source_pipeline(feature_path)
    internal_pipeline = docs_manifest.build_source_pipeline(internal_path)

    assert public_pipeline["pipeline"] == "public-website"
    assert developer_pipeline["pipeline"] == "developer-docs"
    assert feature_pipeline["pipeline"] == "feature-teaser"
    assert internal_pipeline["pipeline"] == "internal-excluded"

    feature_tags = docs_manifest.infer_tags(feature_path, "# Features\n\nThe platform has a feature catalog for vector search, graph queries, and deployment workflows.\n")
    assert "feature" in feature_tags or "architecture" in feature_tags


def test_infer_document_module_groups_developer_docs_by_module():
    developer_path = Path("source-links/docs/api/authentication.md")
    website_path = Path("source-links/docs/website/pages/themisdb.md")
    security_path = Path("source-links/docs/security/krisis.md")

    assert docs_manifest.infer_document_module(developer_path) == "api"
    assert docs_manifest.infer_document_module(website_path) == "website"
    assert docs_manifest.infer_document_module(security_path) == "security"

    grouped = docs_manifest.group_documents_by_module({
        "documents": [
            {"source_path": str(developer_path), "relative_path": "api/authentication.md", "title": "Authentication", "module": "api"},
            {"source_path": str(website_path), "relative_path": "website/pages/themisdb.md", "title": "ThemisDB", "module": "website"},
            {"source_path": str(security_path), "relative_path": "security/krisis.md", "title": "KRITIS", "module": "security"},
        ]
    })
    assert set(grouped.keys()) == {"api", "website", "security"}


def test_frontmatter_round_trip_keeps_metadata_and_hash():
    sample = {
        "title": "Example document",
        "slug": "example-document",
        "category": "documentation",
        "status": "publish",
        "readiness": "ready",
        "score": 88,
        "author": "ThemisDB",
        "coauthors": ["Anthropic Claude", "GitHub Copilot"],
        "tags": ["documentation", "architecture"],
        "language": "en",
        "source_origin": "source-links/docs",
        "source_path": "source-links/docs/website/example-document.md",
        "hash": "abc123def456",
        "backlog": ["Validate public feed filters", "Check related-doc rewrite"],
    }
    block = docs_import.build_frontmatter_block(sample) if hasattr(docs_import, "build_frontmatter_block") else ""
    assert block.startswith("--- ")
    assert block.endswith(" ---\n")
    assert "hash: \"abc123def456\"" in block
    assert "coauthors: [\"Anthropic Claude\", \"GitHub Copilot\"]" in block

    parsed = docs_import.parse_frontmatter(block)
    assert parsed["title"] == "Example document"
    assert parsed["slug"] == "example-document"
    assert parsed["score"] == 88
    assert parsed["tags"] == ["documentation", "architecture"]
    assert parsed["coauthors"] == ["Anthropic Claude", "GitHub Copilot"]
    assert parsed["hash"] == "abc123def456"


def test_lesenswert_tag_is_preserved_in_frontmatter_and_normalization():
    frontmatter = "--- title: \"Lesenswert\"; slug: \"lesenswert\"; tag: lesenswert; category: documentation ---\n"
    parsed = docs_manifest.parse_frontmatter(frontmatter)
    assert parsed["tags"] == ["lesenswert"]
    assert docs_manifest.normalize_tags(["lesenswert", "recommended"]) == ["lesenswert", "recommended"]
    assert docs_manifest.normalize_tags("lesenswert") == ["lesenswert"]


def test_single_string_tags_are_supported_by_wordpress_term_normalization():
    assert docs_import.ensure_taxonomy_terms("lesenswert") == ["lesenswert"]
    assert docs_import.ensure_taxonomy_terms("research, support") == ["research", "support"]
    assert docs_import.primary_category_for_tags("lesenswert") == "lesenswert"


def test_research_tag_requires_doi_when_reviewed_and_published():
    reviewed_frontmatter = "--- title: \"Research paper\"; slug: \"research-paper\"; tag: research; status: publish; reviewed: true; review_status: peer-reviewed; doi: \"10.1234/example\"; category: documentation; hash: \"abc123\" ---\n"
    parsed = docs_manifest.parse_frontmatter(reviewed_frontmatter)
    assert parsed["tags"] == ["research"]
    assert parsed["doi"] == "10.1234/example"
    assert parsed["reviewed"] is True
    assert docs_manifest.validate_frontmatter_metadata(reviewed_frontmatter)["valid"] is True

    invalid_frontmatter = "--- title: \"Research paper\"; slug: \"research-paper\"; tag: research; status: publish; reviewed: true; review_status: peer-reviewed; category: documentation; hash: \"abc123\" ---\n"
    invalid_result = docs_manifest.validate_frontmatter_metadata(invalid_frontmatter)
    assert "missing_research_doi" in invalid_result["issues"]


def test_resolve_term_ids_skips_terms_the_user_cannot_create():
    original = docs_import.rest_request

    def fake_rest_request(base_url, user, password, path, method="GET", payload=None):
        if path == "wp-json/wp/v2/categories?search=architecture&per_page=20":
            return []
        if path == "wp-json/wp/v2/categories":
            raise RuntimeError("REST call failed: POST https://example.com/wp-json/wp/v2/categories -> 401: {\"code\":\"rest_cannot_create\"}")
        if path == "wp-json/wp/v2/categories?search=documentation&per_page=20":
            return [{"id": 42, "slug": "documentation"}]
        return []

    docs_import.rest_request = fake_rest_request
    try:
        assert docs_import.resolve_term_ids("https://example.com", "admin", "pw", "categories", ["documentation", "architecture"]) == [42]
    finally:
        docs_import.rest_request = original
