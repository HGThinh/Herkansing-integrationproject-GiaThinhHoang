{
    'name': 'WordPress Sync via RabbitMQ',
    'version': '1.0',
    'summary': 'Sync Odoo with WordPress  for customers and products',
    'author': 'Thinh',
    'category': 'api',
    'depends': ['base', 'product'],
    'data': [
        'data/ir_cron_data.xml',
    ],
    'installable': True,
    'application': False,
}
