<?php
/*
Plugin Name: Donation
Description: Quyên góp ủng hộ
Version: 1.0.1
Author: John Wick and Grok3
Author URI: https://dev.omni-channel.vn
Plugin URI: https://groupcharming.com
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

define('DONATION_VERSION', '1.0.1');
define('DONATION_DIR', plugin_dir_path(__FILE__));
define('DONATION_URL', plugin_dir_url(__FILE__));

// Tự động cài đặt thư viện và sinh khóa mã hóa
register_activation_hook(__FILE__, 'donation_install_and_activate');
function donation_install_and_activate() {
    if (!current_user_can('install_plugins')) return;

    // Sinh khóa mã hóa
    $encryption_key = base64_encode(openssl_random_pseudo_bytes(32));
    update_option('donation_encryption_key', $encryption_key);

    // Cài thư viện
    $vendor_dir = DONATION_DIR . 'vendor/';
    if (!file_exists($vendor_dir . 'autoload.php')) {
        mkdir($vendor_dir, 0755, true);
        $google_zip = 'https://github.com/googleapis/google-api-php-client/releases/download/v2.15.0/google-api-php-client-2.15.0.zip';
        $zip_content = file_get_contents($google_zip);
        file_put_contents($vendor_dir . 'google.zip', $zip_content);
        $zip = new ZipArchive();
        if ($zip->open($vendor_dir . 'google.zip') === true) {
            $zip->extractTo($vendor_dir);
            $zip->close();
            rename($vendor_dir . 'google-api-php-client-2.15.0', $vendor_dir . 'google');
        }
        unlink($vendor_dir . 'google.zip');

        $phpspreadsheet_zip = 'https://github.com/PHPOffice/PhpSpreadsheet/archive/refs/tags/2.0.0.zip';
        $zip_content = file_get_contents($phpspreadsheet_zip);
        file_put_contents($vendor_dir . 'phpspreadsheet.zip', $zip_content);
        $zip->open($vendor_dir . 'phpspreadsheet.zip');
        $zip->extractTo($vendor_dir);
        $zip->close();
        rename($vendor_dir . 'PhpSpreadsheet-2.0.0', $vendor_dir . 'phpspreadsheet');
        unlink($vendor_dir . 'phpspreadsheet.zip');

        file_put_contents($vendor_dir . 'autoload.php', '<?php
            require_once "' . $vendor_dir . 'google/autoload.php";
            require_once "' . $vendor_dir . 'phpspreadsheet/autoload.php";
        ');
    }
}

// Nạp các tệp
require_once DONATION_DIR . 'includes/license-check.php';
require_once DONATION_DIR . 'includes/admin-page.php';
require_once DONATION_DIR . 'includes/data-import.php';
require_once DONATION_DIR . 'includes/google-api.php';
require_once DONATION_DIR . 'includes/chart-generator.php';
require_once DONATION_DIR . 'includes/shortcode-handler.php';

// Đăng ký post type
add_action('init', 'donation_register_post_type', 0);
function donation_register_post_type() {
    if (!donation_is_license_valid()) return;
    register_post_type('donation_table', [
        'labels' => ['name' => 'Donation Tables'],
        'public' => false,
        'show_ui' => true,
    ]);
}

// Nạp JS/CSS
add_action('admin_enqueue_scripts', 'donation_enqueue_admin_scripts');
function donation_enqueue_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_donation' || !current_user_can('manage_options')) return;
    wp_enqueue_style('donation-admin-css', DONATION_URL . 'assets/css/admin.css', [], DONATION_VERSION);
    wp_enqueue_script('donation-admin-js', DONATION_URL . 'assets/js/admin.js', ['jquery'], DONATION_VERSION, true);
}

add_action('wp_enqueue_scripts', 'donation_enqueue_frontend_scripts');
function donation_enqueue_frontend_scripts() {
    wp_enqueue_style('donation-frontend-css', DONATION_URL . 'assets/css/frontend.css', [], DONATION_VERSION);
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'donation')) {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3/dist/chart.min.js', [], '3', true);
        wp_enqueue_script('donation-frontend-js', DONATION_URL . 'assets/js/frontend.js', ['jquery', 'chart-js'], DONATION_VERSION, true);
        wp_localize_script('donation-frontend-js', 'donation_settings', [
            'isLicensed' => donation_is_license_valid(),
            'bgColor' => get_option('donation_bg_color', '#ffffff'),
            'textColor' => get_option('donation_text_color', '#000000'),
            'width' => get_option('donation_width', '100%'),
            'height' => get_option('donation_height', 'auto')
        ]);
    }
}

// Menu quản trị
add_action('admin_menu', 'donation_admin_menu');
function donation_admin_menu() {
    add_menu_page(
        'Donation',
        'Donation',
        'manage_options',
        'donation',
        'donation_admin_page',
        'dashicons-money-alt',
        25
    );
}

// Mã hóa và giải mã
function donation_encrypt($data) {
    $key = base64_decode(get_option('donation_encryption_key'));
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return ['data' => base64_encode($encrypted), 'iv' => base64_encode($iv)];
}

function donation_decrypt($encrypted_data, $iv) {
    $key = base64_decode(get_option('donation_encryption_key'));
    $iv = base64_decode($iv);
    return openssl_decrypt(base64_decode($encrypted_data), 'aes-256-cbc', $key, 0, $iv);
}

// Hủy lịch đồng bộ
register_deactivation_hook(__FILE__, 'donation_deactivate');
function donation_deactivate() {
    wp_clear_scheduled_hook('donation_sync_hook');
}