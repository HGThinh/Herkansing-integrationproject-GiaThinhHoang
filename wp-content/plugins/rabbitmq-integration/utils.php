<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Get RabbitMQ connection.
 *
 * @return AMQPStreamConnection|false
 */
function rsc_get_rabbitmq_connection() {
    try {
        return new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
    } catch (Exception $e) {
        error_log('RabbitMQ Connection Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get customers HTML table.
 *
 * @return string
 */
function rsc_get_customers_html() {
    ob_start();
    rsc_display_customers();
    return ob_get_clean();
}

/**
 * Display customers in an HTML table.
 */
function rsc_display_customers() {
    global $wpdb;
    $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rsc_customers");

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Naam</th><th>Email</th><th>Acties</th></tr></thead>';
    echo '<tbody>';

    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->name) . '</td>';
        echo '<td>' . esc_html($row->email) . '</td>';
        echo '<td>';
        echo '<button class="rsc-edit-button" data-id="' . esc_attr($row->id) . '">Bewerk</button>';
        echo ' <button class="rsc-delete-button" data-id="' . esc_attr($row->id) . '">Verwijder</button>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
?>
