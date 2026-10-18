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

        $envCandidates = array();
        foreach (array('WP_LOAD_PATH', 'WP_PATH', 'WP_INSTALL_PATH') as $envKey) {
            $value = getenv($envKey);
            if (is_string($value) && '' !== trim($value)) {
                $envCandidates[] = trim($value);
            }
        }

        $defaultLocalRoots = array(
            'C:\\xampp\\htdocs\\wordpress',
            'C:/xampp/htdocs/wordpress',
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'wordpress',
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'wordpress',
        );

        $baseDirs = array(
            __DIR__,
            getcwd() ?: __DIR__,
        );

        foreach ($baseDirs as $base) {
            $base = (string) $base;
            if ('' !== $base) {
                $candidates[] = $base;
            }
        }

        foreach (array_merge($envCandidates, $defaultLocalRoots) as $candidate) {
            $candidates[] = $candidate;
        }

        $seen = array();
        foreach ($candidates as $base) {
            $base = rtrim((string) $base, " \\/");
            if ('' === $base || isset($seen[$base])) {
                continue;
            }
            $seen[$base] = true;

            $dir = $base;
            for ($i = 0; $i < 15; $i++) {
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

            $direct = $base . DIRECTORY_SEPARATOR . 'wp-load.php';
            if (is_file($direct)) {
                return $direct;
            }
        }

        throw new RuntimeException('wp-load.php nicht gefunden. Startpunkte: ' . implode(', ', array_unique($candidates)));
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
    array(
        'display_name' => 'Anthropic Claude',
        'first_name'   => 'Anthropic',
        'last_name'    => 'Claude',
        'user_login'   => 'anthropic-claude',
        'user_email'   => 'claude@themisdb.local',
        'description'  => 'AI Sprachmodell und Co-Author',
        'website'      => 'https://claude.ai/',
    ),
    array(
        'display_name' => 'OpenAI GPT-4o',
        'first_name'   => 'OpenAI',
        'last_name'    => 'GPT-4o',
        'user_login'   => 'openai-gpt-4o',
        'user_email'   => 'gpt4o@themisdb.local',
        'description'  => 'AI Sprachmodell und Co-Author',
        'website'      => 'https://openai.com/',
    ),
    array(
        'display_name' => 'Meta Llama 3',
        'first_name'   => 'Meta',
        'last_name'    => 'Llama 3',
        'user_login'   => 'meta-llama-3',
        'user_email'   => 'llama3@themisdb.local',
        'description'  => 'Open-Source Sprachmodell und Co-Author',
        'website'      => 'https://llama.meta.com/',
    ),
    array(
        'display_name' => 'Mistral Large',
        'first_name'   => 'Mistral',
        'last_name'    => 'Large',
        'user_login'   => 'mistral-large',
        'user_email'   => 'mistral@themisdb.local',
        'description'  => 'AI Sprachmodell und Co-Author',
        'website'      => 'https://mistral.ai/',
    ),
    array(
        'display_name' => 'DeepSeek R1',
        'first_name'   => 'DeepSeek',
        'last_name'    => 'R1',
        'user_login'   => 'deepseek-r1',
        'user_email'   => 'deepseek@themisdb.local',
        'description'  => 'AI Sprachmodell und Co-Author',
        'website'      => 'https://deepseek.com/',
    ),
    array(
        'display_name' => 'OpenAI DALL-E',
        'first_name'   => 'OpenAI',
        'last_name'    => 'DALL-E',
        'user_login'   => 'openai-dall-e',
        'user_email'   => 'dalle@themisdb.local',
        'description'  => 'AI Bildgenerator und Co-Author',
        'website'      => 'https://openai.com/dall-e',
    ),
    array(
        'display_name' => 'Stable Diffusion',
        'first_name'   => 'Stable',
        'last_name'    => 'Diffusion',
        'user_login'   => 'stable-diffusion',
        'user_email'   => 'stable-diffusion@themisdb.local',
        'description'  => 'AI Bildgenerator und Co-Author',
        'website'      => 'https://stability.ai/',
    ),
    array(
        'display_name' => 'Midjourney',
        'first_name'   => 'Midjourney',
        'last_name'    => '',
        'user_login'   => 'midjourney',
        'user_email'   => 'midjourney@themisdb.local',
        'description'  => 'AI Bildgenerator und Co-Author',
        'website'      => 'https://www.midjourney.com/',
    ),
    array(
        'display_name' => 'Black Forest Labs Flux',
        'first_name'   => 'Black Forest Labs',
        'last_name'    => 'Flux',
        'user_login'   => 'blackforest-flux',
        'user_email'   => 'flux@themisdb.local',
        'description'  => 'AI Bildgenerator und Co-Author',
        'website'      => 'https://blackforestlabs.ai/',
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
        $status = get_post_status((int) $found->ID);
        if ('publish' !== $status) {
            $published = wp_update_post(array(
                'ID' => (int) $found->ID,
                'post_status' => 'publish',
            ), true);
            if (is_wp_error($published)) {
                themisdb_v3_setup_ai_out('warning_publish', $login . ' -> ' . $published->get_error_message());
            }
        }
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

    $published = wp_update_post(array(
        'ID' => (int) $newId,
        'post_status' => 'publish',
    ), true);
    if (is_wp_error($published)) {
        themisdb_v3_setup_ai_out('warning_publish', $login . ' -> ' . $published->get_error_message());
    }

    $obj = $coauthors_plus->guest_authors->get_guest_author_by('ID', (int) $newId, true);
    $slug = ($obj && !empty($obj->user_nicename)) ? (string) $obj->user_nicename : $login;
    $created++;
    themisdb_v3_setup_ai_out('created', $login . ' -> ' . $slug);
}

$mode_label = $dry_run ? ' (dry-run)' : '';
themisdb_v3_setup_ai_out('summary', 'created=' . $created . ', existing=' . $existing . ', errors=' . $errors . $mode_label);

exit($errors > 0 ? 2 : 0);
