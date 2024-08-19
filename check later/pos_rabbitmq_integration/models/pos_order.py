# pos_rabbitmq_integration/models/pos_order.py

# Import the necessary modules from Odoo
from odoo import models, fields

import pika
import xml.etree.ElementTree as ET

class POSOrder(models.Model):
    _inherit = 'pos.order'

    def send_order_to_rabbitmq(self, order_data):
        # Define connection parameters
        connection_params = pika.ConnectionParameters('localhost')
        connection = pika.BlockingConnection(connection_params)
        channel = connection.channel()

        # Declare a queue
        channel.queue_declare(queue='pos_orders_test')

        # Convert order data to XML
        order_xml = self.convert_to_xml(order_data)

        # Send the XML data to RabbitMQ
        channel.basic_publish(
            exchange='',
            routing_key='pos_orders_test',
            body=order_xml
        )

        # Close the connection
        connection.close()

    def convert_to_xml(self, order_data):
        # Convert dictionary to XML string
        root = ET.Element("Order")
        for key, value in order_data.items():
            element = ET.SubElement(root, key)
            element.text = str(value)
        return ET.tostring(root, encoding='utf-8').decode('utf-8')

    def action_pos_order_paid(self):
        super(POSOrder, self).action_pos_order_paid()
        order_data = {
            'order_id': self.id,
            'amount_total': self.amount_total,
            'customer_id': self.partner_id.id,
            # Add other relevant fields
        }
        self.send_order_to_rabbitmq(order_data)

