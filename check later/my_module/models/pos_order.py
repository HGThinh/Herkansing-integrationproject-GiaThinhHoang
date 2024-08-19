import pika
import xml.etree.ElementTree as ET
from odoo import models, fields, api

class PosOrder(models.Model):
    _inherit = 'pos.order'

    @api.model
    def create(self, vals):
        order = super(PosOrder, self).create(vals)
        if order.partner_id:
            xml_data = self.convert_customer_to_xml(order.partner_id)
            self.send_to_rabbitmq(xml_data)
        return order

    def convert_customer_to_xml(self, customer):
        customer_data = ET.Element('Customer')
        ET.SubElement(customer_data, 'ID').text = str(customer.id)
        ET.SubElement(customer_data, 'Name').text = customer.name
        ET.SubElement(customer_data, 'Email').text = customer.email or ''
        ET.SubElement(customer_data, 'Phone').text = customer.phone or ''
        ET.SubElement(customer_data, 'Street').text = customer.street or ''
        ET.SubElement(customer_data, 'City').text = customer.city or ''
        ET.SubElement(customer_data, 'Country').text = customer.country_id.name if customer.country_id else ''
        xml_str = ET.tostring(customer_data, encoding='utf-8').decode('utf-8')
        return xml_str

    def send_to_rabbitmq(self, xml_data):
        connection = pika.BlockingConnection(pika.ConnectionParameters(host='localhost'))
        channel = connection.channel()
        channel.queue_declare(queue='pos_customer_info')
        channel.basic_publish(exchange='', routing_key='pos_customer_info', body=xml_data)
        print(f"sent message to RabbitMQ: {xml_data}")
        connection.close()

