<?php
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
 * Content for the 'Test RabbitMQ' admin page.
 */
function rabbitmq_test_admin_page_content() {
    echo '<div class="wrap">';
    echo '<h1>RabbitMQ Test</h1>';
    echo '<p>' . test_rabbitmq_connection() . '</p>';
    echo '</div>';
}
?>
