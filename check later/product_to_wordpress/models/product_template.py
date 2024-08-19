from odoo import models, api
import json

class ProductTemplate(models.Model):
    _inherit = 'product.template'

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
        connector = self.env['rabbitmq.connector']
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
        connector.send_message(message)
