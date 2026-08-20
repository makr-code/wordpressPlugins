<?php
/**
 * Create or remove a timed test announcement post for the front-page announcement bar.
 *
 * Usage:
 *   php tools/create-announcement-test.php [--hours=24] [--title="..."] [--dry-run]
 *   php tools/create-announcement-test.php --cleanup
 */

declare(strict_types=1);

if (!function_exists('themisdb_v3_find_wp_load_path')) {
    function themisdb_v3_find_wp_load_path(): string
    {
        $candidates = array();
        $baseDirs = array(__DIR__, getcwd() ?: __DIR__);

        foreach ($baseDirs as $base) {
            $base = (string) $base;
            if ('' === $base) {
                continue;
            }

            $dir = $base;
            for ($i = 0; $i < 10; $i++) {
                $wpLoad = $dir . DIRECTORY_SEPARATOR . 'wp-load.php';
                if (is_file($wpLoad)) {
                    return $wpLoad;
                }

                $parent = dirname($dir);
                if ($parent === $dir) {
                    break;
                }
                $dir = $parent;
            }

            $candidates[] = $base;
        }

        throw new RuntimeException('wp-load.php nicht gefunden. Startpunkte: ' . implode(', ', $candidates));
    }
}

function themisdb_v3_announcement_out(string $key, string $value): void
{
    echo $key . ': ' . $value . PHP_EOL;
}

function themisdb_v3_arg_value(array $args, string $key, string $default = ''): string
{
    $prefix = '--' . $key . '=';
    foreach ($args as $arg) {
        $arg = (string) $arg;
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}

try {
    require themisdb_v3_find_wp_load_path();
} catch (Throwable $e) {
    themisdb_v3_announcement_out('error', $e->getMessage());
    exit(1);
}

$args = isset($argv) ? (array) $argv : array();
$dryRun = in_array('--dry-run', $args, true);
$cleanup = in_array('--cleanup', $args, true);
$hours = (int) themisdb_v3_arg_value($args, 'hours', '24');
if ($hours < 1) {
    $hours = 1;
}

$title = trim((string) themisdb_v3_arg_value($args, 'title', 'Test Announcement: Wartungsfenster'));
$slug = sanitize_title((string) themisdb_v3_arg_value($args, 'slug', 'test-announcement-bar'));
if ('' === $slug) {
    $slug = 'test-announcement-bar';
}

$excerpt = trim((string) themisdb_v3_arg_value(
    $args,
    'excerpt',
    'Geplanter Testhinweis fuer die Announcement-Bar. Sichtbarkeit endet automatisch.'
));

$nowTs = current_time('timestamp');
$tz = wp_timezone();
$endDt = (new DateTimeImmutable('@' . (string) ($nowTs + ($hours * HOUR_IN_SECONDS))))->setTimezone($tz);
$endAt = $endDt->format('Y-m-d H:i:s');

$existing = get_page_by_path($slug, OBJECT, 'post');

if ($cleanup) {
    if (!$existing instanceof WP_Post) {
        themisdb_v3_announcement_out('cleanup', 'kein Testbeitrag gefunden');
        exit(0);
    }

    if ($dryRun) {
        themisdb_v3_announcement_out('would_delete', (string) $existing->ID . ' (' . $slug . ')');
        exit(0);
    }

    $deleted = wp_trash_post((int) $existing->ID);
    if (!$deleted) {
        themisdb_v3_announcement_out('error', 'Loeschen fehlgeschlagen');
        exit(2);
    }

    themisdb_v3_announcement_out('deleted', (string) $existing->ID . ' (' . $slug . ')');
    exit(0);
}

$cat = get_term_by('slug', 'announcement', 'category');
if (!$cat || is_wp_error($cat)) {
    if ($dryRun) {
        themisdb_v3_announcement_out('would_create_category', 'announcement');
    } else {
        $createdCat = wp_insert_term('Announcement', 'category', array('slug' => 'announcement'));
        if (is_wp_error($createdCat)) {
            themisdb_v3_announcement_out('error', 'Kategorie announcement konnte nicht angelegt werden: ' . $createdCat->get_error_message());
            exit(3);
        }
    }
    $cat = get_term_by('slug', 'announcement', 'category');
}

$catId = ($cat && !is_wp_error($cat) && isset($cat->term_id)) ? (int) $cat->term_id : 0;

$postData = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'post_title' => $title,
    'post_name' => $slug,
    'post_excerpt' => $excerpt,
    'post_content' => $excerpt,
    'post_date' => wp_date('Y-m-d H:i:s', $nowTs - 60, $tz),
);

if ($existing instanceof WP_Post) {
    $postData['ID'] = (int) $existing->ID;
}

if ($dryRun) {
    themisdb_v3_announcement_out('mode', 'dry-run');
    themisdb_v3_announcement_out($existing instanceof WP_Post ? 'would_update' : 'would_create', $slug);
    themisdb_v3_announcement_out('end_at', $endAt);
    themisdb_v3_announcement_out('category_id', (string) $catId);
    exit(0);
}

$postId = wp_insert_post($postData, true);
if (is_wp_error($postId)) {
    themisdb_v3_announcement_out('error', 'Beitrag konnte nicht gespeichert werden: ' . $postId->get_error_message());
    exit(4);
}

if ($catId > 0) {
    wp_set_post_terms((int) $postId, array($catId), 'category', true);
}

update_post_meta((int) $postId, 'announcement_end_at', $endAt);

$permalink = get_permalink((int) $postId);

themisdb_v3_announcement_out($existing instanceof WP_Post ? 'updated' : 'created', (string) $postId);
themisdb_v3_announcement_out('slug', $slug);
themisdb_v3_announcement_out('end_at', $endAt);
themisdb_v3_announcement_out('url', is_string($permalink) ? $permalink : '');

exit(0);
