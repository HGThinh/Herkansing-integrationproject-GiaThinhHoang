{
    'name': 'RabbitMQ Connector',
    'version': '1.0',
    'category': 'Tools',
    'summary': 'Connect Odoo with RabbitMQ',
    'depends': ['base'],
    'data': [
        'views/templates.xml',
        'views/views.xml',
    ],
    'demo':[
        'demo/demo.xml'
    ]
    'installable': True,
    'application': False,
}
