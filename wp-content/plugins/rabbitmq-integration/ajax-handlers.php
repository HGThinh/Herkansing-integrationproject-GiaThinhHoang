<?php
/**
 * Save or update customer data.
 */
function rsc_save_customer() {
    // Check nonce for security
    if (!isset($_POST['rsc_save_customer_nonce']) || !wp_verify_nonce($_POST['rsc_save_customer_nonce'], 'rsc_save_customer_action')) {
        wp_send_json(['success' => false, 'message' => 'Nonce verification failed.']);
        return;
    }

    global $wpdb;
    $id = isset($_POST['rsc-customer-id']) ? intval($_POST['rsc-customer-id']) : 0;
    $name = sanitize_text_field($_POST['rsc-customer-name']);
    $email = sanitize_email($_POST['rsc-customer-email']);
    
    if (empty($name) || empty($email)) {
        wp_send_json(['success' => false, 'message' => 'Please fill in all fields.']);
        return;
    }

    if (!is_email($email)) {
        wp_send_json(['success' => false, 'message' => 'Please enter a valid email address.']);
        return;
    }

    if ($id > 0) {
        $wpdb->update(
            "{$wpdb->prefix}rsc_customers",
            ['name' => $name, 'email' => $email],
            ['id' => $id]
        );
    } else {
        $wpdb->insert(
            "{$wpdb->prefix}rsc_customers",
            ['name' => $name, 'email' => $email]
        );
    }

    // Sync with RabbitMQ
    $connection = rsc_get_rabbitmq_connection();
    if ($connection) {
        try {
            $channel = $connection->channel();
            $channel->queue_declare('customers', false, false, false, false);
            $msg = json_encode(['name' => $name, 'email' => $email]);
            $channel->basic_publish(new AMQPMessage($msg), '', 'customers');
            
            $channel->close();
            $connection->close();
        } catch (Exception $e) {
            error_log('RabbitMQ Publishing Error: ' . $e->getMessage());
            wp_send_json(['success' => false, 'message' => 'Error syncing with RabbitMQ.']);
            return;
        }
    } else {
        wp_send_json(['success' => false, 'message' => 'Failed to connect to RabbitMQ.']);
        return;
    }

    wp_send_json([
        'success' => true,
        'customers' => rsc_get_customers_html()
    ]);
}
add_action('wp_ajax_rsc_save_customer', 'rsc_save_customer');

/**
 * Get customer data.
 */
function rsc_get_customer() {
    global $wpdb;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id > 0) {
        $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rsc_customers WHERE id = %d", $id));
        wp_send_json($customer);
    }
    wp_send_json(null);
}
add_action('wp_ajax_rsc_get_customer', 'rsc_get_customer');

/**
 * Delete customer.
 */
function rsc_delete_customer() {
    global $wpdb;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id > 0) {
        $deleted = $wpdb->delete("{$wpdb->prefix}rsc_customers", ['id' => $id]);
        if ($deleted) {
            wp_send_json(['success' => true]);
        }
    }
    wp_send_json(['success' => false]);
}
add_action('wp_ajax_rsc_delete_customer', 'rsc_delete_customer');
?>
