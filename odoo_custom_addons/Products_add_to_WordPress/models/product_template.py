from odoo import models, api
import json
import os
import pika

class ProductTemplate(models.Model):
    _inherit = 'product.template'
    DEFAULT_QUEUE = 'product_add_to_wordpress'
    @api.model
    def send_message(self, message, queue_name=None):
        if queue_name is None:
            queue_name = self.DEFAULT_QUEUE
        
        # Connection setup inside the function
        host = os.environ.get('ODOO_RABBITMQ_HOST', 'localhost')
        port = int(os.environ.get('ODOO_RABBITMQ_PORT', 5672))
        user = os.environ.get('ODOO_RABBITMQ_USER', 'guest')
        password = os.environ.get('ODOO_RABBITMQ_PASSWORD', 'guest')
        credentials = pika.PlainCredentials(user, password)
        parameters = pika.ConnectionParameters(host, port, '/', credentials)
        connection = pika.BlockingConnection(parameters)

        # Sending the message
        channel = connection.channel()
        channel.queue_declare(queue=queue_name, durable=True)
        channel.basic_publish(exchange='', routing_key=queue_name, body=message)
        connection.close()
        
    @api.model
    def create(self, vals):
        product = super(ProductTemplate, self).create(vals)
        self._send_product_to_wordpress(product)
        return product

    def write(self, vals):
        result = super(ProductTemplate, self).write(vals)
        for product in self:
            self._send_product_to_wordpress(product)
        return result

    def _send_product_to_wordpress(self, product):
        message = json.dumps({
            'action': 'update_product',
            'data': {
                'id': product.id,
                'name': product.name,
                'description': product.description,
                'list_price': product.list_price,
                # Add other fields as needed
            }
        })
        self.send_message(message)
