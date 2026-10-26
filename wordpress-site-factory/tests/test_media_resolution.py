from __future__ import annotations

import importlib.util
import sys
from pathlib import Path


def _load_wp_import_module():
    file_path = Path(__file__).resolve().parents[2] / 'page-content-blog' / 'wp_import.py'
    module_dir = file_path.parent
    if str(module_dir) not in sys.path:
        sys.path.insert(0, str(module_dir))
    spec = importlib.util.spec_from_file_location('wp_import_module', file_path)
    module = importlib.util.module_from_spec(spec)
    assert spec is not None and spec.loader is not None
    spec.loader.exec_module(module)
    return module


def test_resolve_article_image_dir_prefers_populated_source_dir_over_empty_output_dir(tmp_path):
    module = _load_wp_import_module()
    output_root = tmp_path / 'output' / 'article-slug'
    output_root.mkdir(parents=True)
    empty_dir = output_root / 'images'
    empty_dir.mkdir()

    sources_root = tmp_path / 'sources'
    article_dir = sources_root / 'article-slug'
    image_dir = article_dir / 'images'
    image_dir.mkdir(parents=True)
    featured = image_dir / 'featured.png'
    featured.write_bytes(b'fake-image')

    module.SCRIPT_DIR = tmp_path
    module.SOURCES_DIR = sources_root

    resolved = module.resolve_article_image_dir(output_root)

    assert resolved == image_dir
    assert featured.exists()


def test_strip_raw_import_markers_removes_reserved_anchor_tokens():
    module = _load_wp_import_module()
    content = (
        '<p>Intro</p>'
        '<p>IMAGE_ANCHOR: section-01 | Example<br /> IMAGE_PROMPT: hidden</p>'
        '<p>QUOTE_ANCHOR: left | Keep this text</p>'
        '<p>Final paragraph</p>'
    )

    cleaned = module.strip_raw_import_markers(content)

    assert 'IMAGE_ANCHOR' not in cleaned
    assert 'IMAGE_PROMPT' not in cleaned
    assert 'QUOTE_ANCHOR' not in cleaned
    assert 'Final paragraph' in cleaned


def test_preflight_import_checks_ignores_images_in_source_dir(tmp_path):
    module = _load_wp_import_module()
    article_slug = 'article-slug'
    output_root = tmp_path / 'output' / article_slug
    output_root.mkdir(parents=True)
    source_root = tmp_path / 'sources' / article_slug
    image_dir = source_root / 'images'
    image_dir.mkdir(parents=True)
    (image_dir / 'featured.png').write_bytes(b'fake-image')

    module.SOURCES_DIR = tmp_path / 'sources'
    module.SCRIPT_DIR = tmp_path

    markdown_path = output_root / 'stages' / '01_source.md'
    markdown_path.parent.mkdir(parents=True)
    markdown_path.write_text(
        '<!-- IMAGE_ANCHOR: section-01 | Demo -->\n\nText\n',
        encoding='utf-8',
    )

    warnings, errors = module.preflight_import_checks({'_path': output_root}, markdown_path)

    assert not errors
    assert warnings == [] or 'IMAGE_ANCHOR ohne QUOTE_ANCHOR' in warnings[0] or warnings == []


def test_build_blog_media_html_uses_first_context_image_as_featured_fallback(tmp_path):
    module = _load_wp_import_module()
    article_dir = tmp_path / 'sources' / 'article-slug'
    article_dir.mkdir(parents=True)
    article_file = article_dir / 'article.md'
    article_file.write_text('# Demo\n\nText\n', encoding='utf-8')
    image_dir = article_dir / 'images'
    image_dir.mkdir(parents=True)
    context_file = image_dir / 'context-01.png'
    context_file.write_bytes(b'fake-image')

    doc = {'source_path': str(article_file)}
    result = module.build_blog_media_html(doc, 'http://localhost/wordpress', 'test', 'test', dry_run=True)

    assert result['featured_media'] is None
    assert 'themisdb-import-media-featured' in result['html']
    assert 'context-01.png' in result['html']


def test_assign_post_coauthors_uses_guest_author_slugs_not_display_names(monkeypatch):
    module = _load_wp_import_module()
    observed = {}

    class DummyRequest:
        def __init__(self, url, data=None, method=None):
            observed['url'] = url
            observed['data'] = data
            observed['method'] = method
            self.headers = {}

        def add_header(self, key, value):
            self.headers[key] = value

    def fake_urlopen(req, timeout=30):
        observed['timeout'] = timeout
        return object()

    monkeypatch.setattr(module.request, 'Request', DummyRequest)
    monkeypatch.setattr(module.request, 'urlopen', fake_urlopen)
    monkeypatch.setattr(module, 'WP_COAUTHORS_REST_ENABLED', True)
    monkeypatch.setattr(module, 'WP_USER', 'user')
    monkeypatch.setattr(module, 'WP_PASS', 'pass')
    monkeypatch.setattr(module, 'WP_BASE', 'http://localhost/wordpress')
    monkeypatch.setattr(module, 'WP_REST_ROUTE_STYLE', 'wp-json')

    module.assign_coauthors_to_post(42, ['Gemini', 'SwarmUI'])

    assert observed['method'] == 'POST'
    assert 'http://localhost/wordpress/' in observed['url']
    payload = module.parse.parse_qs(observed['data'].decode('utf-8'))
    assert payload['new_authors'] == ['google-gemini, swarmui-grafiker']


def test_detect_article_taxonomy_recognizes_podcast_from_audio_markers():
    module = _load_wp_import_module()

    category, post_type, tags = module.detect_article_taxonomy(
        'Team update',
        'team-update',
        'release-notes',
        'This episode is an audio interview with mp3 download and podcast transcript notes.',
    )

    assert category == 'Podcast'
    assert post_type == 'posts'
    assert tags
