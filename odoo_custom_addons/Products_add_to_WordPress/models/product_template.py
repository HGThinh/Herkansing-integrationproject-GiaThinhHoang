from odoo import models, api
import json
import os
import pika

class ProductTemplate(models.Model):
    _inherit = 'product.template'
    
    def _send_product_to_rabbitmq(self, product, action):
        # Map actions to corresponding RabbitMQ queue names
        action_queue_map = {
            'create_product': 'products_add_to_wordpress',
            'update_product': 'products_update_to_wordpress',
            'delete_product': 'products_delete_to_wordpress'
        }
        
        queue_name = action_queue_map.get(action, 'default_queue')
        
        message = json.dumps({
            'action': action,
            'data': {
                'id': product.id,
                'name': product.name,
                'list_price': product.list_price,
                'standard_price': product.standard_price,
                'default_code': product.default_code,
                'categ_id': product.categ_id.id,
                'uom_id': product.uom_id.id,
                # Add other fields as needed
            }
        })
        self.send_message(message, queue_name)

    @api.model
    def send_message(self, message, queue_name):
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
        self._send_product_to_rabbitmq(product, action='create_product')
        return product

    def write(self, vals):
        result = super(ProductTemplate, self).write(vals)
        for product in self:
            self._send_product_to_rabbitmq(product, action='update_product')
        return result

    def unlink(self):
        for product in self:
            self._send_product_to_rabbitmq(product, action='delete_product')
        result = super(ProductTemplate, self).unlink()
        return result

