from odoo import models, fields, api
import requests
import json

class WordPressConnector(models.Model):
    _name = 'wordpress.connector'
    _description = 'WordPress Connector'

    name = fields.Char('Name', required=True)
    url = fields.Char('WordPress URL', required=True)
    consumer_key = fields.Char('Consumer Key', required=True)
    consumer_secret = fields.Char('Consumer Secret', required=True)

    def sync_product(self, product):
        for connector in self:
            url = f"{connector.url}/wp-json/wc/v3/products"
            auth = (connector.consumer_key, connector.consumer_secret)
            
            data = {
                'name': product.name,
                'type': 'simple',
                'regular_price': str(product.list_price),
                'description': product.description,
                'short_description': product.description_sale,
            }

            response = requests.post(url, json=data, auth=auth)
            if response.status_code != 201:
                raise UserError(f"Failed to sync product to WordPress: {response.text}")

class ProductTemplate(models.Model):
    _inherit = 'product.template'

    sync_to_wordpress = fields.Boolean('Sync to WordPress')

    def write(self, vals):
        res = super(ProductTemplate, self).write(vals)
        if 'sync_to_wordpress' in vals or (self.sync_to_wordpress and set(vals.keys()) & {'name', 'list_price', 'description', 'description_sale'}):
            connectors = self.env['wordpress.connector'].search([])
            for connector in connectors:
                connector.sync_product(self)
        return res
