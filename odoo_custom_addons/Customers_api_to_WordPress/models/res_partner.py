from odoo import models, api
import json
import os
import pika

class ResPartner(models.Model):
    _inherit = 'res.partner'
    
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
        customer = super(ResPartner, self).create(vals)
        self._send_customer_to_rabbitmq(customer, action='create_customer')
        return customer

    def write(self, vals):
        result = super(ResPartner, self).write(vals)
        for customer in self:
            self._send_customer_to_rabbitmq(customer, action='update_customer')
        return result

    def unlink(self):
        for customer in self:
            self._send_customer_to_rabbitmq(customer, action='delete_customer')
        result = super(ResPartner, self).unlink()
        return result

    def _send_customer_to_rabbitmq(self, customer, action):
        # Map actions to corresponding RabbitMQ queue names
        action_queue_map = {
            'create_customer': 'customers_add_to_wordpress',
            'update_customer': 'customers_update_to_wordpress',
            'delete_customer': 'customers_delete_to_wordpress'
        }
        
        queue_name = action_queue_map.get(action, 'default_queue')
        
        message = json.dumps({
            'action': action,
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
        self.send_message(message, queue_name)

