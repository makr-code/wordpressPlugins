<?php
/**
 * Setup AI guest co-authors for Co-Authors Plus.
 *
 * Usage:
 *   php tools/setup-ai-coauthors.php [--dry-run]
 *
 * Options:
 *   --dry-run   Zeigt an, was angelegt wuerden, ohne Aenderungen zu schreiben.
 */

declare(strict_types=1);

if (!function_exists('themisdb_v3_find_wp_load_path')) {
    /**
     * Resolve wp-load.php by scanning current and parent directories.
     */
    function themisdb_v3_find_wp_load_path(): string
    {
        $candidates = array();

        $baseDirs = array(
            __DIR__,
            getcwd() ?: __DIR__,
        );

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

if (!function_exists('themisdb_v3_setup_ai_out')) {
    function themisdb_v3_setup_ai_out(string $key, string $value): void
    {
        echo $key . ': ' . $value . PHP_EOL;
    }
}

try {
    require themisdb_v3_find_wp_load_path();
} catch (Throwable $e) {
    themisdb_v3_setup_ai_out('error', $e->getMessage());
    exit(1);
}

if (!isset($GLOBALS['coauthors_plus']) || !is_object($GLOBALS['coauthors_plus'])) {
    themisdb_v3_setup_ai_out('error', 'Co-Authors-Plus ist nicht geladen.');
    exit(1);
}

$coauthors_plus = $GLOBALS['coauthors_plus'];
if (!isset($coauthors_plus->guest_authors) || !is_object($coauthors_plus->guest_authors)) {
    themisdb_v3_setup_ai_out('error', 'Guest-Author API nicht verfuegbar.');
    exit(1);
}

$authors = array(
    array(
        'display_name' => 'Google Gemini',
        'first_name'   => 'Google',
        'last_name'    => 'Gemini',
        'user_login'   => 'google-gemini',
        'user_email'   => 'gemini@themisdb.local',
        'description'  => 'AI Co-Author',
        'website'      => 'https://gemini.google.com/',
    ),
    array(
        'display_name' => 'GitHub Copilot',
        'first_name'   => 'GitHub',
        'last_name'    => 'Copilot',
        'user_login'   => 'github-copilot',
        'user_email'   => 'copilot@themisdb.local',
        'description'  => 'AI Co-Author',
        'website'      => 'https://github.com/features/copilot',
    ),
    array(
        'display_name' => 'Gemma4 (Lektor)',
        'first_name'   => 'Gemma4',
        'last_name'    => 'Lektor',
        'user_login'   => 'gemma4-lektor',
        'user_email'   => 'gemma4-lektor@themisdb.local',
        'description'  => 'AI Co-Author Rolle: Lektor',
        'website'      => 'https://ai.google.dev/gemma',
    ),
    array(
        'display_name' => 'SwarmUI (Grafiker)',
        'first_name'   => 'SwarmUI',
        'last_name'    => 'Grafiker',
        'user_login'   => 'swarmui-grafiker',
        'user_email'   => 'swarmui-grafiker@themisdb.local',
        'description'  => 'AI Co-Author Rolle: Grafiker',
        'website'      => 'http://127.0.0.1:7801',
    ),
);

$dry_run = in_array('--dry-run', isset($argv) ? (array) $argv : array(), true);

if ($dry_run) {
    themisdb_v3_setup_ai_out('mode', 'dry-run -- keine Aenderungen werden gespeichert');
}

$created = 0;
$existing = 0;
$errors = 0;

foreach ($authors as $spec) {
    $login = (string) $spec['user_login'];

    $found = $coauthors_plus->guest_authors->get_guest_author_by('user_login', $login, true);
    if ($found && !empty($found->ID)) {
        $existing++;
        themisdb_v3_setup_ai_out('exists', $login . ' -> ' . (string) $found->user_nicename);
        continue;
    }

    if ($dry_run) {
        $created++;
        themisdb_v3_setup_ai_out('would_create', $login . ' ("' . (string) $spec['display_name'] . '")');
        continue;
    }

    $newId = $coauthors_plus->guest_authors->create($spec);
    if (is_wp_error($newId)) {
        $errors++;
        themisdb_v3_setup_ai_out('error_create', $login . ' -> ' . $newId->get_error_message());
        continue;
    }

    $obj = $coauthors_plus->guest_authors->get_guest_author_by('ID', (int) $newId, true);
    $slug = ($obj && !empty($obj->user_nicename)) ? (string) $obj->user_nicename : $login;
    $created++;
    themisdb_v3_setup_ai_out('created', $login . ' -> ' . $slug);
}

$mode_label = $dry_run ? ' (dry-run)' : '';
themisdb_v3_setup_ai_out('summary', 'created=' . $created . ', existing=' . $existing . ', errors=' . $errors . $mode_label);

exit($errors > 0 ? 2 : 0);
