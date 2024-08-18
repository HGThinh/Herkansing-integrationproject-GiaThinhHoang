<?php
/*
 * Plugin Name: RabbitMQ Integration
 * Description: A plugin to test RabbitMQ integration with WordPress.
 * Version: 1.0
 * Author: Your Name
 */

require_once __DIR__ . '/vendor/autoload.php'; // Ensure the path is correct

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Test RabbitMQ connection and publish a test message.
 *
 * @return string
 */
function test_rabbitmq_connection() {
    try {
        // Establish connection
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        // Declare an exchange and a queue
        $channel->exchange_declare('test_exchange', 'direct', false, false, false);
        $channel->queue_declare('test_queue', false, false, false, false);
        $channel->queue_bind('test_queue', 'test_exchange');

        // Publish a test message
        $msg = new AMQPMessage('Test Message');
        $channel->basic_publish($msg, 'test_exchange');

        // Close connection
        $channel->close();
        $connection->close();

        return 'RabbitMQ connection successful. Test exchange, queue, and message sent.';
    } catch (Exception $e) {
        return 'Failed to connect to RabbitMQ: ' . $e->getMessage();
    }
}

/**
 * Shortcode to display RabbitMQ test result.
 *
 * @return string
 */
function rabbitmq_test_shortcode() {
    return test_rabbitmq_connection();
}
add_shortcode('rabbitmq_test', 'rabbitmq_test_shortcode');

/**
 * Add admin menu for RabbitMQ test.
 */
function rabbitmq_test_admin_page() {
    add_menu_page(
        'RabbitMQ Test',
        'RabbitMQ Test',
        'manage_options',
        'rabbitmq-test',
        'rabbitmq_test_admin_page_content'
    );
}
add_action('admin_menu', 'rabbitmq_test_admin_page');

/**
 * Content for RabbitMQ test admin page.
 */
function rabbitmq_test_admin_page_content() {
    echo '<div class="wrap">';
    echo '<h1>RabbitMQ Test</h1>';
    echo '<p>' . test_rabbitmq_connection() . '</p>';
    echo '</div>';
}

