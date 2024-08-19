<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
    $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
    if ($connection) {
        try {
            $channel = $connection->channel();
            $channel->exchange_declare('test_exchange', 'direct', false, false, false);
            $channel->queue_declare('customers', false, true, false, false);
            $channel->queue_bind('customers', 'test_exchange');

            // Convert customer data to XML format
            $xml_data = "<customer><name>{$name}</name><email>{$email}</email></customer>";

            // Create the RabbitMQ message with the XML data
            $msg = new AMQPMessage($xml_data);
            $channel->basic_publish($msg, 'test_exchange');

            $channel->close();
            $connection->close();

            wp_send_json(['success' => true, 'message' => 'Customer data synced with RabbitMQ.']);
            return;

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
            // Sync with RabbitMQ - customers_delete queue
            $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
            if ($connection) {
                try {
                    $channel = $connection->channel();
                    $channel->exchange_declare('delete_exchange', 'direct', false, false, false);
                    $channel->queue_declare('customers_delete', false, true, false, false);
                    $channel->queue_bind('customers_delete', 'delete_exchange');

                    // Create the RabbitMQ message with the customer ID
                    $msg = new AMQPMessage("<customer><id>{$id}</id></customer>");
                    $channel->basic_publish($msg, 'delete_exchange');

                    $channel->close();
                    $connection->close();

                    wp_send_json(['success' => true, 'message' => 'Customer deleted and synced with RabbitMQ.']);
                    return;
                } catch (Exception $e) {
                    error_log('RabbitMQ Publishing Error: ' . $e->getMessage());
                    wp_send_json(['success' => false, 'message' => 'Error syncing with RabbitMQ.']);
                    return;
                }
            } else {
                wp_send_json(['success' => false, 'message' => 'Failed to connect to RabbitMQ.']);
                return;
            }
        }
    }
    wp_send_json(['success' => false]);
}
add_action('wp_ajax_rsc_delete_customer', 'rsc_delete_customer');
?>

