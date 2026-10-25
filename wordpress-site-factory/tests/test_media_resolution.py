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
