<?php
function donation_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0, 'type' => 'table'], $atts, 'donation');
    $data = get_post_meta($atts['id'], 'donation_data', true);
    $iv = get_post_meta($atts['id'], 'donation_iv', true);
    if (!$data || !$iv) return '<p>Invalid table data.</p>';

    foreach ($data['rows'] as &$row) {
        $row[1] = donation_decrypt($row[1], $iv);
    }

    ob_start();
    if ($atts['type'] === 'table') {
        ?>
        <div class="donation-table-wrapper" data-id="<?php echo $atts['id']; ?>">
            <table class="donation-table" readonly>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['rows'] as $row) { ?>
                        <tr>
                            <td><?php echo esc_html($row[0]); ?></td>
                            <td><?php echo esc_html($row[1]); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
    } elseif ($atts['type'] === 'chart' && donation_is_license_valid()) {
        donation_generate_chart($atts['id']);
    } else {
        echo '<p>Chart feature requires a valid license.</p>';
    }
    return ob_get_clean();
}
add_shortcode('donation', 'donation_shortcode');