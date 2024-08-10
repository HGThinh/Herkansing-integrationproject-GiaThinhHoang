import pika
from odoo import api, fields, models, _

class RabbitMQConnector(models.Model):
    _name = 'rabbitmq.connector'
    _description = 'RabbitMQ Connector'

    name = fields.Char(string='Name', required=True)
    queue_name = fields.Char(string='Queue Name', required=True)
    message = fields.Text(string='Message')

    def send_message(self):
        # Define RabbitMQ connection parameters
        connection_params = pika.ConnectionParameters(host='rabbitmq')
        
        # Establish connection to RabbitMQ
        connection = pika.BlockingConnection(connection_params)
        channel = connection.channel()

        # Declare a queue
        channel.queue_declare(queue=self.queue_name)

        # Publish the message
        channel.basic_publish(exchange='',
                              routing_key=self.queue_name,
                              body=self.message)
        connection.close()

        return _('Message sent to RabbitMQ')

    def receive_message(self):
        # Define RabbitMQ connection parameters
        connection_params = pika.ConnectionParameters(host='rabbitmq')
        
        # Establish connection to RabbitMQ
        connection = pika.BlockingConnection(connection_params)
        channel = connection.channel()

        # Declare a queue
        channel.queue_declare(queue=self.queue_name)

        # Callback function to handle messages
        def callback(ch, method, properties, body):
            print(f"Received {body}")

        # Set up subscription on the queue
        channel.basic_consume(queue=self.queue_name,
                              on_message_callback=callback,
                              auto_ack=True)

        print('Waiting for messages. To exit press CTRL+C')
        channel.start_consuming()
