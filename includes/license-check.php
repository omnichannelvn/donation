<?php
function donation_is_license_valid() {
    $license_key = get_option('donation_license_key');
    $public_key = get_option('donation_public_key');
    if (!$license_key || !$public_key) return false;

    if (function_exists('lmfwc_get_license')) {
        $license = lmfwc_get_license($license_key);
        if ($license && $license->is_valid()) {
            $domain = parse_url(home_url(), PHP_URL_HOST);
            $response = wp_remote_get("https://lic.phpmylicense.com/verify?key=$license_key&domain=$domain", [
                'timeout' => 15,
                'headers' => ['Authorization' => 'Bearer ' . hash('sha256', $license_key . DONATION_VERSION)]
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if ($body['status'] === 200 && $body['message'] === 'License is valid.') {
                    $signature = $body['signature'] ?? '';
                    $data_to_verify = $body['licensedata']['licensekey'] . $body['licensedata']['domain'];
                    return openssl_verify($data_to_verify, base64_decode($signature), $public_key, OPENSSL_ALGO_SHA256) === 1;
                }
            }
        }
    }
    return false;
}

function donation_deactivate_license() {
    if (isset($_POST['deactivate_license']) && check_admin_referer('donation_license', 'donation_license_nonce')) {
        delete_option('donation_license_key');
        echo '<div class="updated"><p>License deactivated!</p></div>';
    }
}
add_action('admin_init', 'donation_deactivate_license');
