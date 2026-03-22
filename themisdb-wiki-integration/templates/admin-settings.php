<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB - Hybrid Database System                                   ║
╠═════════════════════════════════════════════════════════════════════╣
  File:            admin-settings.php                                 ║
  Version:         0.0.3                                              ║
  Last Modified:   2026-06-01                                         ║
╚═════════════════════════════════════════════════════════════════════╝
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$_twi_page = 'themisdb-wiki-integration';
$_twi_tab  = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';
if (!in_array($_twi_tab, array('settings', 'sync', 'shortcodes'), true)) {
    $_twi_tab = 'settings';
}
$_twi_url = function($tab) use ($_twi_page) {
    return esc_url(admin_url('options-general.php?page=' . $_twi_page . '&tab=' . $tab));
};
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html(get_admin_page_title()); ?>
        <a href="<?php echo $_twi_url('sync'); ?>" class="page-title-action"><?php esc_html_e('Sync & Cache', 'themisdb-wiki-integration'); ?></a>
        <a href="<?php echo $_twi_url('shortcodes'); ?>" class="page-title-action"><?php esc_html_e('Shortcodes', 'themisdb-wiki-integration'); ?></a>
    </h1>
    <hr class="wp-header-end">

    <?php settings_errors(); ?>

    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo $_twi_url('settings'); ?>"
           class="nav-tab <?php echo $_twi_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Einstellungen', 'themisdb-wiki-integration'); ?>
        </a>
        <a href="<?php echo $_twi_url('sync'); ?>"
           class="nav-tab <?php echo $_twi_tab === 'sync' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Sync & Cache', 'themisdb-wiki-integration'); ?>
        </a>
        <a href="<?php echo $_twi_url('shortcodes'); ?>"
           class="nav-tab <?php echo $_twi_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Shortcodes', 'themisdb-wiki-integration'); ?>
        </a>
    </nav>

    <div class="themisdb-tab-content">

        <?php if ($_twi_tab === 'settings'): ?>
        <div class="themisdb-admin-modules">
            <div class="card">
                <h2><?php esc_html_e('Schnellaktionen', 'themisdb-wiki-integration'); ?></h2>
                <p><?php esc_html_e('Wechseln Sie direkt zum Sync-Bereich oder zur Shortcode-Referenz.', 'themisdb-wiki-integration'); ?></p>
                <p>
                    <a href="<?php echo $_twi_url('sync'); ?>" class="button button-secondary"><?php esc_html_e('Sync & Cache', 'themisdb-wiki-integration'); ?></a>
                    <a href="<?php echo $_twi_url('shortcodes'); ?>" class="button button-secondary"><?php esc_html_e('Shortcodes', 'themisdb-wiki-integration'); ?></a>
                </p>
            </div>
            <div class="card">
                <h2><?php esc_html_e('Aktive Quelle', 'themisdb-wiki-integration'); ?></h2>
                <table class="widefat striped">
                    <tbody>
                        <tr><th><?php esc_html_e('Repository', 'themisdb-wiki-integration'); ?></th><td><code><?php echo esc_html(get_option('themisdb_wiki_github_repo', '—')); ?></code></td></tr>
                        <tr><th><?php esc_html_e('Branch', 'themisdb-wiki-integration'); ?></th><td><code><?php echo esc_html(get_option('themisdb_wiki_github_branch', 'main')); ?></code></td></tr>
                        <tr><th><?php esc_html_e('Auto-Sync', 'themisdb-wiki-integration'); ?></th><td><?php echo get_option('themisdb_wiki_auto_sync', 'yes') === 'yes' ? esc_html__('Aktiv', 'themisdb-wiki-integration') : esc_html__('Deaktiviert', 'themisdb-wiki-integration'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <form method="post" action="options.php">
            <?php
            settings_fields('themisdb_wiki_settings');
            do_settings_sections('themisdb_wiki_settings');
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="themisdb_wiki_github_repo"><?php esc_html_e('GitHub Repository', 'themisdb-wiki-integration'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="themisdb_wiki_github_repo"
                               name="themisdb_wiki_github_repo"
                               value="<?php echo esc_attr(get_option('themisdb_wiki_github_repo', 'makr-code/wordpressPlugins')); ?>"
                               class="regular-text" />
                        <p class="description"><?php esc_html_e('GitHub-Repository im Format: owner/repository', 'themisdb-wiki-integration'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="themisdb_wiki_github_branch"><?php esc_html_e('Branch', 'themisdb-wiki-integration'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="themisdb_wiki_github_branch"
                               name="themisdb_wiki_github_branch"
                               value="<?php echo esc_attr(get_option('themisdb_wiki_github_branch', 'main')); ?>"
                               class="regular-text" />
                        <p class="description"><?php esc_html_e('Branch, von dem die Dokumentation geladen wird (z. B. main, develop)', 'themisdb-wiki-integration'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="themisdb_wiki_docs_path"><?php esc_html_e('Dokumentationspfad', 'themisdb-wiki-integration'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="themisdb_wiki_docs_path"
                               name="themisdb_wiki_docs_path"
                               value="<?php echo esc_attr(get_option('themisdb_wiki_docs_path', 'docs')); ?>"
                               class="regular-text" />
                        <p class="description"><?php esc_html_e('Pfad zum Dokumentationsordner im Repository (z. B. docs)', 'themisdb-wiki-integration'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="themisdb_wiki_default_lang"><?php esc_html_e('Standardsprache', 'themisdb-wiki-integration'); ?></label>
                    </th>
                    <td>
                        <select id="themisdb_wiki_default_lang" name="themisdb_wiki_default_lang">
                            <option value="de" <?php selected(get_option('themisdb_wiki_default_lang', 'de'), 'de'); ?>>Deutsch (DE)</option>
                            <option value="en" <?php selected(get_option('themisdb_wiki_default_lang', 'de'), 'en'); ?>>English (EN)</option>
                            <option value="fr" <?php selected(get_option('themisdb_wiki_default_lang', 'de'), 'fr'); ?>>Français (FR)</option>
                        </select>
                        <p class="description"><?php esc_html_e('Standardsprache für die Dokumentationsanzeige', 'themisdb-wiki-integration'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="themisdb_wiki_github_token"><?php esc_html_e('GitHub Token (optional)', 'themisdb-wiki-integration'); ?></label>
                    </th>
                    <td>
                        <input type="password"
                               id="themisdb_wiki_github_token"
                               name="themisdb_wiki_github_token"
                               value="<?php echo esc_attr(get_option('themisdb_wiki_github_token', '')); ?>"
                               class="regular-text"
                               autocomplete="new-password" />
                        <p class="description"><?php esc_html_e('Personal Access Token für private Repositories oder höhere Rate-Limits', 'themisdb-wiki-integration'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Auto-Sync', 'themisdb-wiki-integration'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   id="themisdb_wiki_auto_sync"
                                   name="themisdb_wiki_auto_sync"
                                   value="yes"
                                   <?php checked(get_option('themisdb_wiki_auto_sync', 'yes'), 'yes'); ?> />
                            <?php esc_html_e('Dokumentation stündlich automatisch synchronisieren', 'themisdb-wiki-integration'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Wenn aktiviert, wird die Dokumentation automatisch jede Stunde aktualisiert', 'themisdb-wiki-integration'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(esc_attr__('Einstellungen speichern', 'themisdb-wiki-integration')); ?>
        </form>

        <?php elseif ($_twi_tab === 'sync'): ?>
        <h2><?php esc_html_e('Manuelle Synchronisierung', 'themisdb-wiki-integration'); ?></h2>
        <p><?php esc_html_e('Klicken Sie auf die Schaltfläche, um den Cache zu löschen und Dokumentation sofort von GitHub neu zu laden.', 'themisdb-wiki-integration'); ?></p>

        <p>
            <button type="button" id="themisdb-sync-now" class="button button-primary">
                <?php esc_html_e('Jetzt synchronisieren', 'themisdb-wiki-integration'); ?>
            </button>
        </p>
        <div id="themisdb-sync-message"></div>

        <hr style="margin:24px 0;">

        <h2><?php esc_html_e('Cache-Informationen', 'themisdb-wiki-integration'); ?></h2>
        <table class="widefat striped" style="max-width:600px;">
            <tbody>
                <tr>
                    <th style="width:40%"><?php esc_html_e('Repository', 'themisdb-wiki-integration'); ?></th>
                    <td><code><?php echo esc_html(get_option('themisdb_wiki_github_repo', '—')); ?></code></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Branch', 'themisdb-wiki-integration'); ?></th>
                    <td><code><?php echo esc_html(get_option('themisdb_wiki_github_branch', 'main')); ?></code></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Dokumentationspfad', 'themisdb-wiki-integration'); ?></th>
                    <td><code><?php echo esc_html(get_option('themisdb_wiki_docs_path', 'docs')); ?></code></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Auto-Sync', 'themisdb-wiki-integration'); ?></th>
                    <td><?php echo get_option('themisdb_wiki_auto_sync', 'yes') === 'yes'
                        ? '<span style="color:#008a00">&#10003; Aktiv</span>'
                        : '<span style="color:#d63638">&#10007; Deaktiviert</span>'; ?></td>
                </tr>
            </tbody>
        </table>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#themisdb-sync-now').on('click', function() {
                var $button  = $(this);
                var $message = $('#themisdb-sync-message');
                $button.prop('disabled', true).text('<?php echo esc_js(__('Synchronisiere…', 'themisdb-wiki-integration')); ?>');
                $message.html('');
                $.ajax({
                    url:  ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'themisdb_sync_docs',
                        nonce:  '<?php echo esc_js(wp_create_nonce('themisdb_wiki_nonce')); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $message.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                        } else {
                            $message.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $message.html('<div class="notice notice-error inline"><p><?php echo esc_js(__('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'themisdb-wiki-integration')); ?></p></div>');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('<?php echo esc_js(__('Jetzt synchronisieren', 'themisdb-wiki-integration')); ?>');
                    }
                });
            });
        });
        </script>

        <?php elseif ($_twi_tab === 'shortcodes'): ?>
        <h2><?php esc_html_e('Verfügbare Shortcodes', 'themisdb-wiki-integration'); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Shortcode', 'themisdb-wiki-integration'); ?></th>
                    <th><?php esc_html_e('Beschreibung', 'themisdb-wiki-integration'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[themisdb_wiki file="README.md" lang="de" show_toc="yes"]</code></td>
                    <td><?php esc_html_e('Zeigt eine Markdown-Datei aus dem Repository an, optional mit Inhaltsverzeichnis.', 'themisdb-wiki-integration'); ?></td>
                </tr>
                <tr>
                    <td><code>[themisdb_docs lang="de" layout="grid"]</code></td>
                    <td><?php esc_html_e('Listet alle verfügbaren Dokumentationsdateien in der gewählten Sprache auf.', 'themisdb-wiki-integration'); ?></td>
                </tr>
                <tr>
                    <td><code>[themisdb_wiki_nav]</code></td>
                    <td><?php esc_html_e('Zeigt eine Navigationsleiste für die Dokumentationsstruktur an.', 'themisdb-wiki-integration'); ?></td>
                </tr>
            </tbody>
        </table>

        <h3 style="margin-top:24px;"><?php esc_html_e('Parameter: [themisdb_wiki]', 'themisdb-wiki-integration'); ?></h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Parameter', 'themisdb-wiki-integration'); ?></th>
                    <th><?php esc_html_e('Beschreibung', 'themisdb-wiki-integration'); ?></th>
                    <th><?php esc_html_e('Optionen', 'themisdb-wiki-integration'); ?></th>
                    <th><?php esc_html_e('Standard', 'themisdb-wiki-integration'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>file</code></td>
                    <td><?php esc_html_e('Markdown-Datei (relativ zum Dokumentationspfad)', 'themisdb-wiki-integration'); ?></td>
                    <td><?php esc_html_e('z. B. README.md, features/features.md', 'themisdb-wiki-integration'); ?></td>
                    <td>—</td>
                </tr>
                <tr>
                    <td><code>lang</code></td>
                    <td><?php esc_html_e('Anzeigesprache', 'themisdb-wiki-integration'); ?></td>
                    <td>de, en, fr</td>
                    <td><?php echo esc_html(get_option('themisdb_wiki_default_lang', 'de')); ?></td>
                </tr>
                <tr>
                    <td><code>show_toc</code></td>
                    <td><?php esc_html_e('Inhaltsverzeichnis anzeigen', 'themisdb-wiki-integration'); ?></td>
                    <td>yes, no</td>
                    <td>no</td>
                </tr>
            </tbody>
        </table>

        <h3 style="margin-top:24px;"><?php esc_html_e('Parameter: [themisdb_docs]', 'themisdb-wiki-integration'); ?></h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Parameter', 'themisdb-wiki-integration'); ?></th>
                    <th><?php esc_html_e('Beschreibung', 'themisdb-wiki-integration'); ?></th>
                    <th><?php esc_html_e('Optionen', 'themisdb-wiki-integration'); ?></th>
                    <th><?php esc_html_e('Standard', 'themisdb-wiki-integration'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>lang</code></td>
                    <td><?php esc_html_e('Sprache der angezeigten Dateien', 'themisdb-wiki-integration'); ?></td>
                    <td>de, en, fr</td>
                    <td><?php echo esc_html(get_option('themisdb_wiki_default_lang', 'de')); ?></td>
                </tr>
                <tr>
                    <td><code>layout</code></td>
                    <td><?php esc_html_e('Darstellungsformat', 'themisdb-wiki-integration'); ?></td>
                    <td>list, grid</td>
                    <td>list</td>
                </tr>
            </tbody>
        </table>

        <?php endif; ?>

    </div><!-- .themisdb-tab-content -->
</div><!-- .wrap -->

<style>
.themisdb-admin-modules { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px; margin:0 0 20px; }
.themisdb-admin-modules .card { margin:0; max-width:none; }
.themisdb-tab-content { background:#fff; border:1px solid #c3c4c7; border-top:none; padding:20px 24px; }
.themisdb-tab-content > h2:first-child,
.themisdb-tab-content > h3:first-child,
.themisdb-tab-content > p:first-child { margin-top:0; }
.themisdb-tab-content .widefat th { width:auto; }
.themisdb-tab-content table.widefat code { background:#f6f7f7; padding:2px 6px; border-radius:3px; font-size:12px; }
</style>
