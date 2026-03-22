<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB Support Portal – Admin Settings Template                   ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}

$themisdb_support_page = 'themisdb-support-portal';
$themisdb_support_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'settings';
$themisdb_support_tabs = array('settings', 'shortcodes');

if (!in_array($themisdb_support_tab, $themisdb_support_tabs, true)) {
    $themisdb_support_tab = 'settings';
}

$themisdb_support_tab_url = static function ($tab) use ($themisdb_support_page) {
    return admin_url('options-general.php?page=' . $themisdb_support_page . '&tab=' . $tab);
};

$themisdb_support_redirect_url = get_option('themisdb_support_redirect_url', home_url('/'));
$themisdb_support_email_notifications = get_option('themisdb_support_email_notifications', '1');
$themisdb_support_admin_email = get_option('themisdb_support_admin_email', get_option('admin_email'));
$themisdb_support_email_from_name = get_option('themisdb_support_email_from_name', get_option('blogname'));
$themisdb_support_email_from = get_option('themisdb_support_email_from', get_option('admin_email'));
?>
<div class="wrap themisdb-support-admin-wrap">
    <style>
        .themisdb-tab-content {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-top: none;
            padding: 20px 24px;
        }

        .themisdb-tab-content > :first-child,
        .themisdb-tab-content .themisdb-admin-modules:first-child,
        .themisdb-tab-content .card:first-child,
        .themisdb-tab-content form:first-child {
            margin-top: 0;
        }

        .themisdb-admin-modules {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            margin: 0 0 24px;
        }

        .themisdb-admin-modules .card,
        .themisdb-tab-content .card {
            margin: 0;
            max-width: none;
            padding: 20px 24px;
        }

        .themisdb-tab-toolbar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 0 0 16px;
        }

        .themisdb-tab-content .widefat thead th {
            font-weight: 600;
        }
    </style>

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e('ThemisDB Support – Einstellungen', 'themisdb-support-portal'); ?>
    </h1>
    <a href="<?php echo esc_url($themisdb_support_tab_url('settings')); ?>" class="page-title-action"><?php esc_html_e('Einstellungen bearbeiten', 'themisdb-support-portal'); ?></a>
    <a href="<?php echo esc_url($themisdb_support_tab_url('shortcodes')); ?>" class="page-title-action"><?php esc_html_e('Shortcodes anzeigen', 'themisdb-support-portal'); ?></a>
    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Support Portal Einstellungen', 'themisdb-support-portal'); ?>">
        <a href="<?php echo esc_url($themisdb_support_tab_url('settings')); ?>" class="nav-tab <?php echo $themisdb_support_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Einstellungen', 'themisdb-support-portal'); ?>
        </a>
        <a href="<?php echo esc_url($themisdb_support_tab_url('shortcodes')); ?>" class="nav-tab <?php echo $themisdb_support_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Shortcodes', 'themisdb-support-portal'); ?>
        </a>
    </nav>

    <div class="themisdb-tab-content">
        <?php if ($themisdb_support_tab === 'settings') : ?>
            <div class="themisdb-admin-modules">
                <div class="card">
                    <h2><?php esc_html_e('Schnellaktionen', 'themisdb-support-portal'); ?></h2>
                    <div class="themisdb-tab-toolbar">
                        <a href="#themisdb-support-settings-form" class="button button-primary"><?php esc_html_e('Zur Konfiguration', 'themisdb-support-portal'); ?></a>
                        <a href="<?php echo esc_url($themisdb_support_tab_url('shortcodes')); ?>" class="button"><?php esc_html_e('Shortcode-Referenz', 'themisdb-support-portal'); ?></a>
                    </div>
                    <p><?php esc_html_e('Verwalte Redirects und E-Mail-Benachrichtigungen für Login, Ticket-Erstellung und Antworten zentral an einer Stelle.', 'themisdb-support-portal'); ?></p>
                </div>

                <div class="card">
                    <h2><?php esc_html_e('Aktive Defaults', 'themisdb-support-portal'); ?></h2>
                    <table class="widefat striped">
                        <tbody>
                            <tr>
                                <th><?php esc_html_e('Redirect nach Login', 'themisdb-support-portal'); ?></th>
                                <td><?php echo esc_html($themisdb_support_redirect_url); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Benachrichtigungen', 'themisdb-support-portal'); ?></th>
                                <td><?php echo esc_html($themisdb_support_email_notifications === '1' ? 'Aktiv' : 'Inaktiv'); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Admin-E-Mail', 'themisdb-support-portal'); ?></th>
                                <td><?php echo esc_html($themisdb_support_admin_email); ?></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Absender', 'themisdb-support-portal'); ?></th>
                                <td><?php echo esc_html($themisdb_support_email_from_name . ' <' . $themisdb_support_email_from . '>'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <form id="themisdb-support-settings-form" method="post" action="options.php">
                <?php
                settings_fields('themisdb_support_settings');
                do_settings_sections('themisdb_support_settings');
                ?>

                <table class="form-table" role="presentation">
                    <tr valign="top">
                        <th scope="row">
                            <label for="themisdb_support_redirect_url">
                                <?php esc_html_e('Weiterleitungs-URL nach Anmeldung', 'themisdb-support-portal'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="url" id="themisdb_support_redirect_url" name="themisdb_support_redirect_url"
                                value="<?php echo esc_attr($themisdb_support_redirect_url); ?>"
                                class="regular-text">
                            <p class="description">
                                <?php esc_html_e('URL auf die der Nutzer nach erfolgreicher Lizenzdatei-Anmeldung weitergeleitet wird.', 'themisdb-support-portal'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('E-Mail-Benachrichtigungen', 'themisdb-support-portal'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="themisdb_support_email_notifications"
                                    name="themisdb_support_email_notifications" value="1"
                                    <?php checked('1', $themisdb_support_email_notifications); ?>>
                                <?php esc_html_e('E-Mail-Benachrichtigungen aktivieren (für neue Tickets und Antworten)', 'themisdb-support-portal'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="themisdb_support_admin_email">
                                <?php esc_html_e('Admin-E-Mail für Benachrichtigungen', 'themisdb-support-portal'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email" id="themisdb_support_admin_email" name="themisdb_support_admin_email"
                                value="<?php echo esc_attr($themisdb_support_admin_email); ?>"
                                class="regular-text">
                            <p class="description">
                                <?php esc_html_e('An diese E-Mail-Adresse werden Benachrichtigungen über neue Tickets gesendet.', 'themisdb-support-portal'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="themisdb_support_email_from_name">
                                <?php esc_html_e('Absendername für E-Mails', 'themisdb-support-portal'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="themisdb_support_email_from_name" name="themisdb_support_email_from_name"
                                value="<?php echo esc_attr($themisdb_support_email_from_name); ?>"
                                class="regular-text">
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label for="themisdb_support_email_from">
                                <?php esc_html_e('Absender-E-Mail-Adresse', 'themisdb-support-portal'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email" id="themisdb_support_email_from" name="themisdb_support_email_from"
                                value="<?php echo esc_attr($themisdb_support_email_from); ?>"
                                class="regular-text">
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Einstellungen speichern', 'themisdb-support-portal')); ?>
            </form>
        <?php else : ?>
            <div class="themisdb-admin-modules">
                <div class="card">
                    <h2><?php esc_html_e('Schnellaktionen', 'themisdb-support-portal'); ?></h2>
                    <div class="themisdb-tab-toolbar">
                        <a href="<?php echo esc_url($themisdb_support_tab_url('settings')); ?>" class="button button-primary"><?php esc_html_e('Einstellungen öffnen', 'themisdb-support-portal'); ?></a>
                    </div>
                    <p><?php esc_html_e('Binde das Support-Portal als komplettes Ticket-System oder als reines Login-Formular in Seiten und Landingpages ein.', 'themisdb-support-portal'); ?></p>
                </div>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Shortcode-Referenz', 'themisdb-support-portal'); ?></h2>
                <table class="widefat striped" style="max-width: 760px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Shortcode', 'themisdb-support-portal'); ?></th>
                            <th><?php esc_html_e('Beschreibung', 'themisdb-support-portal'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[themisdb_support_portal]</code></td>
                            <td><?php esc_html_e('Vollständiges Support-Portal (Login-Formular oder Ticket-System)', 'themisdb-support-portal'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[themisdb_support_login]</code></td>
                            <td><?php esc_html_e('Nur das Lizenzdatei-Anmeldeformular', 'themisdb-support-portal'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
