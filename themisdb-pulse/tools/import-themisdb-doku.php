<?php
/**
 * Import markdown files from ThemisDB_Doku as WordPress posts.
 *
 * Usage (from WordPress root):
 * php wp-content/themes/themisdb-theme-v3/tools/import-themisdb-doku.php --source="C:/Projects/ThemisDB_Doku" --type=post --status=draft --update=1 --limit=0 --auto-tags=1 --safe-publish=1
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must run in CLI mode.\n");
    exit(1);
}

$wpLoad = themisdb_find_wp_load();

if ($wpLoad === '') {
    fwrite(STDERR, "Could not find wp-load.php from script location.\n");
    exit(1);
}

require_once $wpLoad;

if (!function_exists('wp_insert_post')) {
    fwrite(STDERR, "WordPress bootstrap failed.\n");
    exit(1);
}

$options = themisdb_parse_cli_options($argv);
$sourceDir = isset($options['source']) ? (string) $options['source'] : 'C:/Projects/ThemisDB_Doku';
$postType = isset($options['type']) ? sanitize_key((string) $options['type']) : 'post';
$postStatus = isset($options['status']) ? sanitize_key((string) $options['status']) : 'draft';
$updateExisting = themisdb_to_bool(isset($options['update']) ? $options['update'] : '1');
$limit = isset($options['limit']) ? max(0, (int) $options['limit']) : 0;
$categoryName = isset($options['category']) ? trim((string) $options['category']) : 'ThemisDB Doku';
$tagNames = themisdb_parse_tag_names(isset($options['tags']) ? (string) $options['tags'] : 'themisdb,doku');
$autoTags = themisdb_to_bool(isset($options['auto-tags']) ? $options['auto-tags'] : '1');
$safePublish = themisdb_to_bool(isset($options['safe-publish']) ? $options['safe-publish'] : '1');
$minPublishWords = isset($options['min-publish-words']) ? max(0, (int) $options['min-publish-words']) : 180;

$sourceDir = str_replace('\\', '/', $sourceDir);
if (!is_dir($sourceDir)) {
    fwrite(STDERR, "Source directory not found: {$sourceDir}\n");
    exit(1);
}

if (!post_type_exists($postType)) {
    fwrite(STDERR, "Post type does not exist: {$postType}\n");
    exit(1);
}

if (!in_array($postStatus, array('draft', 'publish', 'pending', 'private'), true)) {
    fwrite(STDERR, "Invalid status '{$postStatus}'. Allowed: draft, publish, pending, private\n");
    exit(1);
}

$categoryId = 0;
if ($categoryName !== '' && is_object_in_taxonomy($postType, 'category')) {
    $categoryId = themisdb_ensure_term('category', $categoryName);
}

$supportsTags = is_object_in_taxonomy($postType, 'post_tag');

$files = themisdb_collect_markdown_files($sourceDir);
if (empty($files)) {
    fwrite(STDOUT, "No markdown files found in {$sourceDir}\n");
    exit(0);
}

if ($limit > 0) {
    $files = array_slice($files, 0, $limit);
}

$imported = 0;
$updated = 0;
$skipped = 0;
$failed = 0;

foreach ($files as $filePath) {
    $raw = file_get_contents($filePath);
    if ($raw === false) {
        fwrite(STDERR, "[FAIL] Cannot read file: {$filePath}\n");
        $failed++;
        continue;
    }

    $relative = ltrim(str_replace(str_replace('\\', '/', $sourceDir), '', str_replace('\\', '/', $filePath)), '/');
    $title = themisdb_extract_markdown_title($raw, basename($filePath, '.md'));
    $html = themisdb_markdown_to_basic_html($raw);
    $excerpt = themisdb_extract_excerpt_from_html($html, 42);
    $wordCount = themisdb_count_words_from_html($html);
    $effectiveStatus = $postStatus;

    if ('publish' === $postStatus && $safePublish && $wordCount < $minPublishWords) {
        $effectiveStatus = 'draft';
        fwrite(STDOUT, "[SAFE] {$relative}: {$wordCount} words below publish threshold {$minPublishWords}, using draft\n");
    }

    $combinedTagNames = $tagNames;
    if ($autoTags) {
        $combinedTagNames = array_values(
            array_unique(
                array_merge($combinedTagNames, themisdb_generate_auto_tags($relative, $title, $html))
            )
        );
    }

    if (trim(wp_strip_all_tags($html)) === '') {
        fwrite(STDOUT, "[SKIP] Empty content: {$relative}\n");
        $skipped++;
        continue;
    }

    $existingId = themisdb_find_existing_post_by_source($relative, $postType);

    if ($existingId > 0 && !$updateExisting) {
        fwrite(STDOUT, "[SKIP] Exists (no update): {$relative} (#{$existingId})\n");
        $skipped++;
        continue;
    }

    $postarr = array(
        'post_title' => $title,
        'post_name' => sanitize_title(pathinfo($relative, PATHINFO_FILENAME)),
        'post_content' => $html,
        'post_excerpt' => $excerpt,
        'post_status' => $effectiveStatus,
        'post_type' => $postType,
    );

    if ($existingId > 0) {
        $postarr['ID'] = $existingId;
        $result = wp_update_post($postarr, true);
    } else {
        $result = wp_insert_post($postarr, true);
    }

    if (is_wp_error($result) || (int) $result <= 0) {
        $message = is_wp_error($result) ? $result->get_error_message() : 'unknown error';
        fwrite(STDERR, "[FAIL] {$relative}: {$message}\n");
        $failed++;
        continue;
    }

    $postId = (int) $result;
    update_post_meta($postId, '_themisdb_doku_source', $relative);
    update_post_meta($postId, '_themisdb_doku_source_mtime', (string) @filemtime($filePath));
    update_post_meta($postId, '_themisdb_doku_word_count', $wordCount);
    update_post_meta($postId, '_themisdb_doku_auto_tags', wp_json_encode($combinedTagNames));

    if ($categoryId > 0) {
        wp_set_post_terms($postId, array($categoryId), 'category', false);
    }

    if ($supportsTags && !empty($combinedTagNames)) {
        $tagIds = array();
        foreach ($combinedTagNames as $tagName) {
            $termId = themisdb_ensure_term('post_tag', $tagName);
            if ($termId > 0) {
                $tagIds[] = $termId;
            }
        }

        if (!empty($tagIds)) {
            wp_set_post_terms($postId, $tagIds, 'post_tag', false);
        }
    }

    if ($existingId > 0) {
        fwrite(STDOUT, "[UPDATE] {$relative} -> #{$postId}\n");
        $updated++;
    } else {
        fwrite(STDOUT, "[IMPORT] {$relative} -> #{$postId}\n");
        $imported++;
    }
}

fwrite(STDOUT, "Done. imported={$imported}, updated={$updated}, skipped={$skipped}, failed={$failed}\n");
exit($failed > 0 ? 2 : 0);

function themisdb_parse_cli_options(array $argv)
{
    $options = array();
    foreach ($argv as $arg) {
        if (strpos($arg, '--') !== 0) {
            continue;
        }

        $arg = substr($arg, 2);
        if ($arg === '') {
            continue;
        }

        $parts = explode('=', $arg, 2);
        $key = sanitize_key($parts[0]);
        $value = isset($parts[1]) ? $parts[1] : '1';

        if ($key !== '') {
            $options[$key] = $value;
        }
    }
    return $options;
}

function themisdb_find_wp_load()
{
    $current = __DIR__;

    for ($i = 0; $i < 8; $i++) {
        $candidate = rtrim($current, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wp-load.php';
        if (file_exists($candidate)) {
            return $candidate;
        }

        $parent = dirname($current);
        if ($parent === $current) {
            break;
        }
        $current = $parent;
    }

    $cwdCandidate = rtrim(getcwd(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'wp-load.php';
    if (file_exists($cwdCandidate)) {
        return $cwdCandidate;
    }

    return '';
}

function themisdb_to_bool($value)
{
    $value = strtolower(trim((string) $value));
    return in_array($value, array('1', 'true', 'yes', 'on'), true);
}

function themisdb_parse_tag_names($value)
{
    $value = (string) $value;
    if (trim($value) === '') {
        return array();
    }

    $rawItems = preg_split('/\s*,\s*/', $value);
    if (!is_array($rawItems)) {
        return array();
    }

    $names = array();
    foreach ($rawItems as $item) {
        $item = trim((string) $item);
        if ($item !== '') {
            $names[] = $item;
        }
    }

    return array_values(array_unique($names));
}

function themisdb_ensure_term($taxonomy, $name)
{
    $taxonomy = sanitize_key((string) $taxonomy);
    $name = trim((string) $name);
    if ($taxonomy === '' || $name === '' || !taxonomy_exists($taxonomy)) {
        return 0;
    }

    $existing = term_exists($name, $taxonomy);
    if (is_array($existing) && isset($existing['term_id'])) {
        return (int) $existing['term_id'];
    }
    if (is_int($existing)) {
        return (int) $existing;
    }

    $created = wp_insert_term($name, $taxonomy);
    if (is_wp_error($created) || !is_array($created) || empty($created['term_id'])) {
        return 0;
    }

    return (int) $created['term_id'];
}

function themisdb_collect_markdown_files($sourceDir)
{
    $files = array();
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $entry) {
        if (!$entry->isFile()) {
            continue;
        }

        $ext = strtolower($entry->getExtension());
        if ($ext !== 'md') {
            continue;
        }

        $files[] = str_replace('\\', '/', $entry->getPathname());
    }

    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
    return $files;
}

function themisdb_extract_markdown_title($raw, $fallback)
{
    if (preg_match('/^#\s+(.+)$/m', (string) $raw, $m)) {
        $title = themisdb_normalize_import_title((string) $m[1]);
        if ($title !== '') {
            return $title;
        }
    }

    $fallback = str_replace(array('_', '-'), ' ', (string) $fallback);
    $fallback = preg_replace('/\s+/', ' ', $fallback);
    return themisdb_normalize_import_title((string) $fallback);
}

function themisdb_normalize_import_title($title)
{
    $title = (string) $title;
    $title = wp_strip_all_tags($title);
    $title = preg_replace('/^#+\s*/u', '', $title);
    $title = preg_replace('/[*_`~]+/u', '', $title);
    $title = preg_replace('/\[(.*?)\]\((.*?)\)/u', '$1', $title);
    $title = preg_replace('/^[^\p{L}\p{N}]+/u', '', $title);
    $title = preg_replace('/\s+/u', ' ', $title);
    return trim((string) $title, " \t\n\r\0\x0B-–—:;,.!?");
}

function themisdb_find_existing_post_by_source($relativeSource, $postType)
{
    $ids = get_posts(array(
        'post_type' => $postType,
        'post_status' => 'any',
        'numberposts' => 1,
        'fields' => 'ids',
        'meta_key' => '_themisdb_doku_source',
        'meta_value' => (string) $relativeSource,
        'suppress_filters' => false,
    ));

    if (empty($ids)) {
        return 0;
    }

    return (int) $ids[0];
}

function themisdb_markdown_to_basic_html($markdown)
{
    $markdown = str_replace(array("\r\n", "\r"), "\n", (string) $markdown);
    $lines = explode("\n", $markdown);

    $out = array();
    $paragraph = array();
    $listType = '';
    $listItems = array();
    $inCode = false;
    $codeLines = array();

    $flushParagraph = function () use (&$paragraph, &$out) {
        if (empty($paragraph)) {
            return;
        }

        $text = trim(implode(' ', $paragraph));
        if ($text !== '') {
            $out[] = '<p>' . themisdb_format_inline_md($text) . '</p>';
        }
        $paragraph = array();
    };

    $flushList = function () use (&$listType, &$listItems, &$out) {
        if ($listType === '' || empty($listItems)) {
            $listType = '';
            $listItems = array();
            return;
        }

        $out[] = '<' . $listType . '><li>' . implode('</li><li>', $listItems) . '</li></' . $listType . '>';
        $listType = '';
        $listItems = array();
    };

    $flushCode = function () use (&$inCode, &$codeLines, &$out) {
        if (!$inCode) {
            return;
        }

        $out[] = '<pre><code>' . esc_html(implode("\n", $codeLines)) . '</code></pre>';
        $inCode = false;
        $codeLines = array();
    };

    foreach ($lines as $line) {
        $trim = trim($line);

        if (preg_match('/^```/', $trim)) {
            if ($inCode) {
                $flushCode();
            } else {
                $flushParagraph();
                $flushList();
                $inCode = true;
                $codeLines = array();
            }
            continue;
        }

        if ($inCode) {
            $codeLines[] = rtrim($line, "\n");
            continue;
        }

        if ($trim === '') {
            $flushParagraph();
            $flushList();
            continue;
        }

        if (preg_match('/^(#{1,6})\s+(.+)$/', $trim, $m)) {
            $flushParagraph();
            $flushList();
            $level = strlen((string) $m[1]);
            $out[] = '<h' . $level . '>' . themisdb_format_inline_md((string) $m[2]) . '</h' . $level . '>';
            continue;
        }

        if (preg_match('/^[-*+]\s+(.+)$/', $trim, $m)) {
            $flushParagraph();
            if ($listType !== 'ul') {
                $flushList();
                $listType = 'ul';
            }
            $listItems[] = themisdb_format_inline_md((string) $m[1]);
            continue;
        }

        if (preg_match('/^\d+\.\s+(.+)$/', $trim, $m)) {
            $flushParagraph();
            if ($listType !== 'ol') {
                $flushList();
                $listType = 'ol';
            }
            $listItems[] = themisdb_format_inline_md((string) $m[1]);
            continue;
        }

        $paragraph[] = $trim;
    }

    $flushParagraph();
    $flushList();
    $flushCode();

    $html = implode("\n", $out);
    return wp_kses_post($html);
}

function themisdb_format_inline_md($text)
{
    $text = esc_html((string) $text);

    $text = preg_replace_callback(
        '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/',
        function ($m) {
            $label = isset($m[1]) ? (string) $m[1] : '';
            $url = isset($m[2]) ? (string) $m[2] : '';
            return '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        },
        $text
    );

    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

    return (string) $text;
}

function themisdb_extract_excerpt_from_html($html, $wordLimit)
{
    $text = wp_strip_all_tags((string) $html);
    $text = trim(preg_replace('/\s+/', ' ', $text));

    if ($text === '') {
        return '';
    }

    $wordLimit = max(8, (int) $wordLimit);
    $words = preg_split('/\s+/', $text);
    if (!is_array($words)) {
        return '';
    }

    if (count($words) <= $wordLimit) {
        return $text;
    }

    return implode(' ', array_slice($words, 0, $wordLimit)) . '...';
}

function themisdb_count_words_from_html($html)
{
    $text = wp_strip_all_tags((string) $html);
    $text = trim(preg_replace('/\s+/', ' ', $text));

    if ($text === '') {
        return 0;
    }

    $words = preg_split('/\s+/u', $text);
    if (!is_array($words)) {
        return 0;
    }

    return count(array_filter($words, static function ($word) {
        return trim((string) $word) !== '';
    }));
}

function themisdb_generate_auto_tags($relativeSource, $title, $html)
{
    $text = strtolower(
        remove_accents(
            wp_strip_all_tags((string) $relativeSource . ' ' . (string) $title . ' ' . themisdb_extract_excerpt_from_html($html, 60))
        )
    );

    $patterns = array(
        '/\b(ai|ki|kunstliche intelligenz)\b/' => 'KI',
        '/\bllm\b/' => 'LLM',
        '/\brag\b/' => 'RAG',
        '/\bhyperscaler\b/' => 'Hyperscaler',
        '/\bsharding\b/' => 'Sharding',
        '/\braid\b/' => 'RAID',
        '/\bmvcc\b/' => 'MVCC',
        '/\bbenchmark\b/' => 'Benchmark',
        '/\barchitektur|architecture\b/' => 'Architektur',
        '/\bsicherheit|security\b/' => 'Sicherheit',
        '/\bethik|ethics\b/' => 'Ethik',
        '/\bcloud\b/' => 'Cloud',
        '/\bverwaltung|administration|government\b/' => 'Verwaltung',
        '/\bsouveranitat|sovereign\b/' => 'Souveränität',
        '/\bvergleich|comparison\b/' => 'Vergleich',
        '/\bstrategie|strategic\b/' => 'Strategie',
        '/\banalyse|analysis\b/' => 'Analyse',
        '/\bmilitar|defense\b/' => 'Militär',
        '/\bc\+\+|cpp\b/' => 'C++',
        '/\brust\b/' => 'Rust'
    );

    $tags = array();
    foreach ($patterns as $pattern => $tag) {
        if (preg_match($pattern, $text)) {
            $tags[] = $tag;
        }
    }

    return array_values(array_unique($tags));
}
