<?php
require_once 'vendor/autoload.php'; // Ensure Composer autoload is included

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// RabbitMQ connection parameters
$host = 'localhost';
$port = 5672;
$user = 'guest';
$pass = 'guest';
$queue = 'customers_delete_to_odoo'; // Queue name for customer deletions

try {
    // Create a new connection
    $connection = new AMQPStreamConnection($host, $port, $user, $pass);
    $channel = $connection->channel();

    // Declare the queue (make sure it matches the queue name used in RabbitMQ)
    $channel->queue_declare($queue, false, true, false, false);

    // Define a callback function to handle messages
    $callback = function($msg) {
        echo 'Received message: ', $msg->body, "\n";

        // Decode the XML message
        $xml = simplexml_load_string($msg->body);
        $id = (int) $xml->id;

        if ($id > 0) {
            global $wpdb;
            $deleted = $wpdb->delete("{$wpdb->prefix}rsc_customers", ['id' => $id]);

            if ($deleted) {
                echo "Customer with ID {$id} deleted from WordPress.\n";
            } else {
                echo "Failed to delete customer with ID {$id}.\n";
            }
        }
    };

    // Set up the consumer
    $channel->basic_consume($queue, '', false, true, false, false, $callback);

    // Wait for messages
    while($channel->is_consuming()) {
        $channel->wait();
    }

} catch (Exception $e) {
    error_log('RabbitMQ Consumer Error: ' . $e->getMessage());
} finally {
    // Cleanup
    if (isset($channel)) $channel->close();
    if (isset($connection)) $connection->close();
}
?>
