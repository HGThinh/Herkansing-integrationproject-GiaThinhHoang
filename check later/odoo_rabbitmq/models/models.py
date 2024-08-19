import os
import pika
from odoo import models, api

class RabbitMQConnector(models.AbstractModel):
    _name = 'rabbitmq.connector'
    _description = 'RabbitMQ Connector'

    DEFAULT_QUEUE = 'odoo_to_wp'

    @api.model
    def connect_to_rabbitmq(self):
        host = os.environ.get('ODOO_RABBITMQ_HOST', 'localhost')
        port = int(os.environ.get('ODOO_RABBITMQ_PORT', 5672))
        user = os.environ.get('ODOO_RABBITMQ_USER', 'guest')
        password = os.environ.get('ODOO_RABBITMQ_PASSWORD', 'guest')
        credentials = pika.PlainCredentials(user, password)
        parameters = pika.ConnectionParameters(host, port, '/', credentials)
        connection = pika.BlockingConnection(parameters)
        return connection

    @api.model
    def send_message(self, message, queue_name=None):
        if queue_name is None:
            queue_name = self.DEFAULT_QUEUE
        connection = self.connect_to_rabbitmq()
        channel = connection.channel()
        channel.queue_declare(queue=queue_name, durable=True)
        channel.basic_publish(exchange='', routing_key=queue_name, body=message)
        connection.close()

    @api.model
    def receive_message(self, queue_name=None):
        if queue_name is None:
            queue_name = self.DEFAULT_QUEUE
        connection = self.connect_to_rabbitmq()
        channel = connection.channel()
        channel.queue_declare(queue=queue_name, durable=True)
        method_frame, header_frame, body = channel.basic_get(queue=queue_name)
        if method_frame:
            channel.basic_ack(method_frame.delivery_tag)
            connection.close()
            return body.decode()
        else:
            connection.close()
            return None
