import json
import pika
import logging
from odoo import models, api
from xml.etree import ElementTree as ET

_logger = logging.getLogger(__name__)

class RabbitMQConsumer(models.TransientModel):
    _name = 'rabbitmq.consumer'
    _description = 'Consume from WordPress for Odoo Sync'

    @api.model
    def start_consuming(self):
        # RabbitMQ connection setup
        connection = pika.BlockingConnection(pika.ConnectionParameters('localhost'))
        channel = connection.channel()

        # Declare queues for customers and products
        channel.queue_declare(queue='customers_add_to_odoo')
        channel.queue_declare(queue='products_add_to_odoo')
        channel.queue_declare(queue='customers_update_to_odoo')
        channel.queue_declare(queue='products_update_to_odoo')
        channel.queue_declare(queue='customers_delete_to_odoo')
        channel.queue_declare(queue='products_delete_to_odoo')

        def callback(ch, method, properties, body):
            _logger.info(f"Received new message: {body}")
            message = body.decode()  # Convert bytes to string
            
            # Parse the XML message
            root = ET.fromstring(message)
            action = root.find('action').text
            data = self._parse_xml_to_dict(root.find('data'))

            if action == 'add_product':
                self.add_product(data)
            elif action == 'update_product':
                self.update_product(data)
            elif action == 'delete_product':
                self.delete_product(data)
            else:
                _logger.warning(f"Unhandled action: {action}")

        # Start consuming
        channel.basic_consume(queue='products_add_to_odoo', on_message_callback=callback, auto_ack=True)
        channel.basic_consume(queue='products_update_to_odoo', on_message_callback=callback, auto_ack=True)
        channel.basic_consume(queue='products_delete_to_odoo', on_message_callback=callback, auto_ack=True)
        
        _logger.info("Starting consume from WordPress...")
        channel.start_consuming()

    @api.model
    def add_product(self, data):
        _logger.info(f"Adding product with data: {data}")
        
        # Create a new product record
        if 'name' in data and 'list_price' in data:
            product = self.env['product.product'].create(data)
            _logger.info(f"Product {product.id} added.")
        else:
            _logger.error("Invalid data for adding a product.")

    @api.model
    def update_product(self, data):
        _logger.info(f"Updating product with data: {data}")
        
        # Update an existing product
        product_id = data.get('id')
        if product_id:
            product = self.env['product.product'].search([('id', '=', product_id)], limit=1)
            if product:
                updates = {}
                if 'name' in data:
                    updates['name'] = data['name']
                if 'list_price' in data:
                    updates['list_price'] = data['list_price']
                
                if updates:
                    product.write(updates)
                    _logger.info(f"Product {product_id} updated with {updates}.")
                else:
                    _logger.warning(f"No 'name' or 'list_price' provided to update for product {product_id}.")
            else:
                _logger.warning(f"Product {product_id} not found.")
        else:
            _logger.error("No product ID provided in the data.")

    @api.model
    def delete_product(self, data):
        _logger.info(f"Deleting product with data: {data}")
        
        # Delete an existing product
        product_id = data.get('id')
        if product_id:
            product = self.env['product.product'].search([('id', '=', product_id)], limit=1)
            if product:
                product.unlink()
                _logger.info(f"Product {product_id} deleted.")
            else:
                _logger.warning(f"Product {product_id} not found.")
        else:
            _logger.error("No product ID provided in the data.")

    def _parse_xml_to_dict(self, element):
        """Helper method to convert XML element to a dictionary."""
        result = {}
        for child in element:
            result[child.tag] = child.text
        return result

