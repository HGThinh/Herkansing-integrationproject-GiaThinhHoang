{
    'name': 'POS RabbitMQ Integration',
    'version': '1.0',
    'summary': 'Send POS orders as XML to RabbitMQ',
    'description': """
        This module sends POS orders as XML files to a RabbitMQ server.
        It overrides the POS order workflow to trigger the sending of orders upon payment.
    """,
    'category': 'Point of Sale',
    'author': 'Thinh',
    'website': 'localhost',
    'depends': ['point_of_sale'],
    'data': [
        # List of XML files containing data such as views, reports, etc.
        'views/pos_rabbitmq_integration_view.xml',
    ],
    'installable': True,
    'application': False,
    'auto_install': False,
    'external_dependencies': {
        'python': ['pika'],  # External Python libraries that the module depends on
    },
}

