<?php
function donation_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $token = get_option('donation_google_access_token');
    $license_key = get_option('donation_license_key');
    $public_key = get_option('donation_public_key');

    if (isset($_POST['save_license']) && check_admin_referer('donation_license', 'donation_license_nonce')) {
        $new_license = sanitize_text_field($_POST['license_key']);
        $new_public_key = sanitize_textarea_field($_POST['public_key']);
        update_option('donation_license_key', $new_license);
        update_option('donation_public_key', $new_public_key);
        echo '<div class="updated"><p>License settings saved! Checking validity...</p></div>';
    }

    if (isset($_POST['save_google_api']) && check_admin_referer('donation_api', 'donation_api_nonce')) {
        update_option('donation_google_client_id', sanitize_text_field($_POST['google_client_id']));
        update_option('donation_google_client_secret', sanitize_text_field($_POST['google_client_secret']));
        echo '<div class="updated"><p>Google API settings saved!</p></div>';
    }

    if (isset($_POST['save_display_settings']) && donation_is_license_valid() && check_admin_referer('donation_display', 'donation_display_nonce')) {
        update_option('donation_bg_color', sanitize_hex_color($_POST['bg_color']));
        update_option('donation_text_color', sanitize_hex_color($_POST['text_color']));
        update_option('donation_width', sanitize_text_field($_POST['width']));
        update_option('donation_height', sanitize_text_field($_POST['height']));
        echo '<div class="updated"><p>Display settings saved!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Donation - Quyên góp ủng hộ (v<?php echo DONATION_VERSION; ?>)</h1>

        <!-- Form License -->
        <h2>License Activation</h2>
        <form method="post">
            <?php wp_nonce_field('donation_license', 'donation_license_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label>License Key</label></th>
                    <td>
                        <input type="text" name="license_key" value="<?php echo esc_attr($license_key); ?>" class="regular-text">
                        <p class="description">Get your key from <a href="https://lic.phpmylicense.com/">PHPMyLicense</a>.</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Public Key</label></th>
                    <td>
                        <textarea name="public_key" rows="5" class="regular-text"><?php echo esc_textarea($public_key); ?></textarea>
                        <p class="description">Enter the public key from PHPMyLicense.</p>
                    </td>
                </tr>
            </table>
            <input type="submit" name="save_license" value="Activate License" class="button-primary">
            <?php if ($license_key && donation_is_license_valid()) { ?>
                <input type="submit" name="deactivate_license" value="Deactivate License" class="button-secondary">
            <?php } ?>
        </form>

        <!-- Form Google API -->
        <?php if (!$token) { ?>
            <h2>Google Sheets API Settings</h2>
            <form method="post">
                <?php wp_nonce_field('donation_api', 'donation_api_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label>Client ID</label></th>
                        <td><input type="text" name="google_client_id" value="<?php echo esc_attr(get_option('donation_google_client_id')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label>Client Secret</label></th>
                        <td><input type="text" name="google_client_secret" value="<?php echo esc_attr(get_option('donation_google_client_secret')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <input type="submit" name="save_google_api" value="Save Settings" class="button-primary">
            </form>
        <?php } else { ?>
            <h2>Google Sheets API</h2>
            <p>API configured. <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=donation&reset_api=1'), 'donation_reset_api'); ?>" class="button">Replace API Settings</a></p>
        <?php } ?>

        <!-- Form tạo bảng -->
        <h2>Create New Table</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('donation_import', 'donation_nonce'); ?>
            <label>Data Source:</label>
            <select name="data_source">
                <option value="excel" <?php echo !donation_is_license_valid() ? 'disabled' : ''; ?>>Excel/CSV</option>
                <option value="google_sheets">Google Sheets (API)</option>
            </select>
            <input type="file" name="excel_file" accept=".csv,.xlsx" <?php echo !donation_is_license_valid() ? 'disabled' : ''; ?>>
            <input type="text" name="google_spreadsheet_id" placeholder="Google Spreadsheet ID">
            <input type="text" name="google_range" placeholder="Range (e.g., Sheet1!A1:D10)">
            <input type="submit" value="Create Table" class="button-primary">
        </form>

        <!-- Tùy chỉnh giao diện -->
        <?php if (donation_is_license_valid()) { ?>
            <h2>Display Settings</h2>
            <form method="post">
                <?php wp_nonce_field('donation_display', 'donation_display_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label>Background Color</label></th>
                        <td><input type="color" name="bg_color" value="<?php echo esc_attr(get_option('donation_bg_color', '#ffffff')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>Text Color</label></th>
                        <td><input type="color" name="text_color" value="<?php echo esc_attr(get_option('donation_text_color', '#000000')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label>Width</label></th>
                        <td><input type="text" name="width" value="<?php echo esc_attr(get_option('donation_width', '100%')); ?>" class="regular-text" placeholder="e.g., 500px or 100%"></td>
                    </tr>
                    <tr>
                        <th><label>Height</label></th>
                        <td><input type="text" name="height" value="<?php echo esc_attr(get_option('donation_height', 'auto')); ?>" class="regular-text" placeholder="e.g., 300px or auto"></td>
                    </tr>
                </table>
                <input type="submit" name="save_display_settings" value="Save Display Settings" class="button-primary">
            </form>
        <?php } ?>
    </div>
    <?php
    if (isset($_GET['reset_api']) && $_GET['reset_api'] == 1 && check_admin_referer('donation_reset_api')) {
        delete_option('donation_google_access_token');
        wp_redirect(admin_url('admin.php?page=donation'));
        exit;
    }
}
