from odoo import models, api
import json

class ResPartner(models.Model):
    _inherit = 'res.partner'

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
        connector = self.env['rabbitmq.connector']
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
        connector.send_message(message)

