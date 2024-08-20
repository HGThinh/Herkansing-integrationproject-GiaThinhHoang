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
                    $channel->queue_declare('customers_delete_to_odoo', false, true, false, false);
                    $channel->queue_bind('customers_delete_to_odoo', 'delete_exchange');

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



/**
 * Save or update product data.
 */
function rsc_save_product() {
    if (!isset($_POST['rsc_save_product_nonce']) || !wp_verify_nonce($_POST['rsc_save_product_nonce'], 'rsc_save_product_action')) {
        wp_send_json(['success' => false, 'message' => 'Nonce verification failed.']);
        return;
    }

    global $wpdb;
    $id = isset($_POST['rsc-product-id']) ? intval($_POST['rsc-product-id']) : 0;
    $name = sanitize_text_field($_POST['rsc-product-name']);
    $list_price = floatval($_POST['rsc-product-list-price']);
    
    if (empty($name) || $list_price < 0) {
        wp_send_json(['success' => false, 'message' => 'Please fill in all fields correctly.']);
        return;
    }

    try {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        if ($id > 0) {
            $result = $wpdb->update(
                "{$wpdb->prefix}rsc_products",
                ['name' => $name, 'list_price' => $list_price],
                ['id' => $id]
            );

            if ($result === false) {
                wp_send_json(['success' => false, 'message' => 'Failed to update product in database.']);
                return;
            }

            $exchange = 'update_product_exchange';
        } else {
            $result = $wpdb->insert(
                "{$wpdb->prefix}rsc_products",
                ['name' => $name, 'list_price' => $list_price]
            );

            if ($result === false) {
                wp_send_json(['success' => false, 'message' => 'Failed to insert product into database.']);
                return;
            }

            $exchange = 'add_product_exchange';
        }

        $xml_data = "<product><id>{$id}</id><name>{$name}</name><list_price>{$list_price}</list_price></product>";
        $msg = new AMQPMessage($xml_data);
        $channel->exchange_declare($exchange, 'direct', false, false, false);
        $channel->queue_declare('products_to_odoo', false, true, false, false);
        $channel->queue_bind('products_to_odoo', $exchange);
        $channel->basic_publish($msg, $exchange);

        $channel->close();
        $connection->close();

        wp_send_json(['success' => true, 'message' => 'Product data saved and synced with RabbitMQ.']);
    } catch (Exception $e) {
        error_log('RabbitMQ Error: ' . $e->getMessage());
        wp_send_json(['success' => false, 'message' => 'Error syncing with RabbitMQ.']);
    }
}

add_action('wp_ajax_rsc_save_product', 'rsc_save_product');

/**
 * Delete product.
 */
function rsc_delete_product() {
    if (!isset($_POST['rsc_delete_product_nonce']) || !wp_verify_nonce($_POST['rsc_delete_product_nonce'], 'rsc_delete_product_action')) {
        wp_send_json(['success' => false, 'message' => 'Nonce verification failed.']);
        return;
    }

    global $wpdb;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id <= 0) {
        wp_send_json(['success' => false, 'message' => 'Invalid product ID.']);
        return;
    }

    try {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $result = $wpdb->delete("{$wpdb->prefix}rsc_products", ['id' => $id]);

        if ($result === false) {
            wp_send_json(['success' => false, 'message' => 'Failed to delete product from database.']);
            return;
        }

        $xml_data = "<product><id>{$id}</id></product>";
        $msg = new AMQPMessage($xml_data);
        $channel->exchange_declare('delete_product_exchange', 'direct', false, false, false);
        $channel->queue_declare('products_delete_to_odoo', false, true, false, false);
        $channel->queue_bind('products_delete_to_odoo', 'delete_product_exchange');
        $channel->basic_publish($msg, 'delete_product_exchange');

        $channel->close();
        $connection->close();

        wp_send_json(['success' => true, 'message' => 'Product deleted and message sent to RabbitMQ.']);
    } catch (Exception $e) {
        error_log('RabbitMQ Error: ' . $e->getMessage());
        wp_send_json(['success' => false, 'message' => 'Error syncing with RabbitMQ.']);
    }
}

add_action('wp_ajax_rsc_delete_product', 'rsc_delete_product');

/**
 * Get product.
 */
function rsc_get_product() {
    if (!isset($_GET['id']) || !wp_verify_nonce($_GET['_wpnonce'], 'rsc_get_product_action')) {
        wp_send_json(['success' => false, 'message' => 'Nonce verification failed.']);
        return;
    }

    global $wpdb;
    $id = intval($_GET['id']);

    if ($id <= 0) {
        wp_send_json(['success' => false, 'message' => 'Invalid product ID.']);
        return;
    }

    $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rsc_products WHERE id = %d", $id));

    if ($product) {
        wp_send_json(['success' => true, 'data' => $product]);
    } else {
        wp_send_json(['success' => false, 'message' => 'Product not found.']);
    }
}

add_action('wp_ajax_rsc_get_product', 'rsc_get_product');

?>

