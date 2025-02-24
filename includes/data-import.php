<?php
require_once DONATION_DIR . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

function donation_import_data() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !current_user_can('manage_options') || !wp_verify_nonce($_POST['donation_nonce'], 'donation_import')) {
        return;
    }

    $table_data = [];
    $source = $_POST['data_source'] ?? '';

    if ($source === 'excel' && isset($_FILES['excel_file']) && donation_is_license_valid()) {
        $file = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $raw_data = $sheet->toArray();
        $table_data = ['headers' => array_shift($raw_data), 'rows' => array_filter($raw_data)];
    } elseif ($source === 'google_sheets' && !empty($_POST['google_spreadsheet_id'])) {
        $spreadsheet_id = sanitize_text_field($_POST['google_spreadsheet_id']);
        $range = sanitize_text_field($_POST['google_range']);
        $table_data = donation_fetch_google_sheets($spreadsheet_id, $range);
    } else {
        return;
    }

    foreach ($table_data['rows'] as &$row) {
        $encrypted = donation_encrypt($row[1]);
        $row[1] = $encrypted['data'];
        $iv = $encrypted['iv'];
    }

    $table_id = wp_insert_post(['post_title' => 'Donation Table ' . time(), 'post_type' => 'donation_table', 'post_status' => 'private']);
    update_post_meta($table_id, 'donation_data', $table_data);
    update_post_meta($table_id, 'donation_iv', $iv);
    if ($source === 'google_sheets') {
        update_post_meta($table_id, 'donation_spreadsheet_id', $spreadsheet_id);
        update_post_meta($table_id, 'donation_range', $range);
    }
}
add_action('admin_init', 'donation_import_data');