from odoo import models, api
import json
import os
import pika

class ResPartner(models.Model):
    _inherit = 'res.partner'
    DEFAULT_QUEUE = 'customers_add_to_wordpress'
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
        customer = super(ResPartner, self).create(vals)
        self._send_customer_to_rabbitmq(customer)
        return customer

    def write(self, vals):
        result = super(ResPartner, self).write(vals)
        for customer in self:
            self._send_customer_to_rabbitmq(customer)
        return result

    def _send_customer_to_rabbitmq(self, customer):
        message = json.dumps({
            'action': 'update_customer',
            'data': {
                'id': customer.id,
                'name': customer.name,
                'email': customer.email,
                'phone': customer.phone,
                'street': customer.street,
                'city': customer.city,
                'zip': customer.zip,
                'country_id': customer.country_id.id,
                # Add other fields as needed
            }
        })
        self.send_message(message)

