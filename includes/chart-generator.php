<?php
function donation_generate_chart($table_id) {
    if (!donation_is_license_valid()) return;
    $data = get_post_meta($table_id, 'donation_data', true);
    $iv = get_post_meta($table_id, 'donation_iv', true);
    foreach ($data['rows'] as &$row) {
        $row[1] = donation_decrypt($row[1], $iv);
    }

    $labels = array_column($data['rows'], 0);
    $values = array_column($data['rows'], 1);
    ?>
    <canvas id="donation-chart-<?php echo $table_id; ?>" width="400" height="200"></canvas>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('donation-chart-<?php echo $table_id; ?>'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: '<?php echo esc_js($data['headers'][1]); ?>',
                        data: <?php echo json_encode($values); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: { scales: { y: { beginAtZero: true } } }
            });
        });
    </script>
    <?php
}