<?php
/*
╔═════════════════════════════════════════════════════════════════════╗
║ ThemisDB Support Portal – Admin Settings Template                   ║
╚═════════════════════════════════════════════════════════════════════╝
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap themisdb-support-admin-wrap">
    <h1>
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e('ThemisDB Support – Einstellungen', 'themisdb-support-portal'); ?>
    </h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('themisdb_support_settings');
        do_settings_sections('themisdb_support_settings');
        ?>

        <table class="form-table" role="presentation">

            <!-- Post-login redirect URL -->
            <tr valign="top">
                <th scope="row">
                    <label for="themisdb_support_redirect_url">
                        <?php esc_html_e('Weiterleitungs-URL nach Anmeldung', 'themisdb-support-portal'); ?>
                    </label>
                </th>
                <td>
                    <input type="url" id="themisdb_support_redirect_url" name="themisdb_support_redirect_url"
                        value="<?php echo esc_attr(get_option('themisdb_support_redirect_url', home_url('/'))); ?>"
                        class="regular-text">
                    <p class="description">
                        <?php esc_html_e('URL auf die der Nutzer nach erfolgreicher Lizenzdatei-Anmeldung weitergeleitet wird.', 'themisdb-support-portal'); ?>
                    </p>
                </td>
            </tr>

            <!-- Email notifications toggle -->
            <tr valign="top">
                <th scope="row">
                    <?php esc_html_e('E-Mail-Benachrichtigungen', 'themisdb-support-portal'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="themisdb_support_email_notifications"
                            name="themisdb_support_email_notifications" value="1"
                            <?php checked('1', get_option('themisdb_support_email_notifications', '1')); ?>>
                        <?php esc_html_e('E-Mail-Benachrichtigungen aktivieren (für neue Tickets und Antworten)', 'themisdb-support-portal'); ?>
                    </label>
                </td>
            </tr>

            <!-- Admin notification email -->
            <tr valign="top">
                <th scope="row">
                    <label for="themisdb_support_admin_email">
                        <?php esc_html_e('Admin-E-Mail für Benachrichtigungen', 'themisdb-support-portal'); ?>
                    </label>
                </th>
                <td>
                    <input type="email" id="themisdb_support_admin_email" name="themisdb_support_admin_email"
                        value="<?php echo esc_attr(get_option('themisdb_support_admin_email', get_option('admin_email'))); ?>"
                        class="regular-text">
                    <p class="description">
                        <?php esc_html_e('An diese E-Mail-Adresse werden Benachrichtigungen über neue Tickets gesendet.', 'themisdb-support-portal'); ?>
                    </p>
                </td>
            </tr>

            <!-- Sender name -->
            <tr valign="top">
                <th scope="row">
                    <label for="themisdb_support_email_from_name">
                        <?php esc_html_e('Absendername für E-Mails', 'themisdb-support-portal'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="themisdb_support_email_from_name" name="themisdb_support_email_from_name"
                        value="<?php echo esc_attr(get_option('themisdb_support_email_from_name', get_option('blogname'))); ?>"
                        class="regular-text">
                </td>
            </tr>

            <!-- Sender email -->
            <tr valign="top">
                <th scope="row">
                    <label for="themisdb_support_email_from">
                        <?php esc_html_e('Absender-E-Mail-Adresse', 'themisdb-support-portal'); ?>
                    </label>
                </th>
                <td>
                    <input type="email" id="themisdb_support_email_from" name="themisdb_support_email_from"
                        value="<?php echo esc_attr(get_option('themisdb_support_email_from', get_option('admin_email'))); ?>"
                        class="regular-text">
                </td>
            </tr>

        </table>

        <?php submit_button(__('Einstellungen speichern', 'themisdb-support-portal')); ?>
    </form>

    <!-- Shortcode reference -->
    <div class="themisdb-support-admin-card" style="margin-top:30px;">
        <h2><?php esc_html_e('Shortcode-Referenz', 'themisdb-support-portal'); ?></h2>
        <table class="widefat" style="max-width:600px;">
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
</div>
