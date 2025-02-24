<?php
require_once DONATION_DIR . 'vendor/autoload.php';
use Google\Client;
use Google\Service\Sheets;

function donation_get_google_client() {
    $client = new Client();
    $client->setApplicationName('Donation Plugin');
    $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
    $client->setClientId(get_option('donation_google_client_id'));
    $client->setClientSecret(get_option('donation_google_client_secret'));
    $client->setRedirectUri(admin_url('admin.php?page=donation'));

    $token = get_option('donation_google_access_token');
    if (!$token && !isset($_GET['code'])) {
        $auth_url = $client->createAuthUrl();
        echo '<p><a href="' . esc_url($auth_url) . '">Authorize Google Sheets API</a></p>';
        return null;
    }
    $client->setAccessToken($token);

    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        update_option('donation_google_access_token', $client->getAccessToken());
    }
    return $client;
}

function donation_fetch_google_sheets($spreadsheet_id, $range) {
    $client = donation_get_google_client();
    if (!$client) return [];
    $service = new Sheets($client);
    $response = $service->spreadsheets_values->get($spreadsheet_id, $range);
    $values = $response->getValues();
    return ['headers' => array_shift($values), 'rows' => array_filter($values)];
}

function donation_sync_google_sheets() {
    $tables = get_posts(['post_type' => 'donation_table', 'posts_per_page' => -1]);
    foreach ($tables as $table) {
        $spreadsheet_id = get_post_meta($table->ID, 'donation_spreadsheet_id', true);
        $range = get_post_meta($table->ID, 'donation_range', true);
        if (!$spreadsheet_id || !$range) continue;

        $cache_key = 'donation_cache_' . $table->ID;
        if (get_transient($cache_key) !== false) continue;

        $table_data = donation_fetch_google_sheets($spreadsheet_id, $range);
        foreach ($table_data['rows'] as &$row) {
            $encrypted = donation_encrypt($row[1]);
            $row[1] = $encrypted['data'];
            $iv = $encrypted['iv'];
        }

        update_post_meta($table->ID, 'donation_data', $table_data);
        update_post_meta($table->ID, 'donation_iv', $iv);
        set_transient($cache_key, $table_data, 15 * MINUTE_IN_SECONDS);
    }
}

if (!wp_next_scheduled('donation_sync_hook')) {
    wp_schedule_event(time(), '15_minutes', 'donation_sync_hook');
}
add_action('donation_sync_hook', 'donation_sync_google_sheets');

add_filter('cron_schedules', 'donation_add_cron_interval');
function donation_add_cron_interval($schedules) {
    $schedules['15_minutes'] = ['interval' => 900, 'display' => __('Every 15 Minutes')];
    return $schedules;
}

add_action('admin_init', 'donation_handle_google_callback');
function donation_handle_google_callback() {
    if (isset($_GET['code']) && $_GET['page'] === 'donation') {
        $client = donation_get_google_client();
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        update_option('donation_google_access_token', $token);
        wp_redirect(admin_url('admin.php?page=donation'));
        exit;
    }
}