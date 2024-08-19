import pika
import requests
import json
import base64

# RabbitMQ connection parameters
rabbitmq_host = 'localhost'  
rabbitmq_queue = 'odoo_to_wp'

# WordPress API endpoint and authentication details
wordpress_api_url = 'http://127.0.0.1:8000/'  
wordpress_username = 'Student'
wordpress_password = 'Boobbb0202'

# Base64 encode the credentials for Basic Authentication
wordpress_credentials = f'{wordpress_username}:{wordpress_password}'
encoded_credentials = base64.b64encode(wordpress_credentials.encode()).decode()

def callback(ch, method, properties, body):
    # Decode the message received from RabbitMQ
    message = json.loads(body)
    
    # Send the data to WordPress using a POST request
    response = requests.post(
        wordpress_api_url,
        headers={
            'Authorization': f'Basic {encoded_credentials}',
            'Content-Type': 'application/json'
        },
        json=message  # Send the message as JSON
    )
    
    print("Status Code:", response.status_code)
    print("Headers:", response.headers)
    
    if response.headers.get('Content-Type', '').startswith('application/json'):
        try:
            response_data = response.json()
            print('Data sent successfully to WordPress:', response_data)
        except requests.exceptions.JSONDecodeError:
            print('Failed to parse JSON response:', response.text)
    else:
        print('Received non-JSON response:')
        print(response.text)
    if response.status_code == 200 or response.status_code == 201:
        print('Data sent successfully to WordPress:', response.json())
        # Acknowledge message delivery to RabbitMQ
        ch.basic_ack(delivery_tag=method.delivery_tag)
    else:
        print('Failed to send data to WordPress:', response.text)
        # Optionally, you can nack the message so that it can be reprocessed
        ch.basic_nack(delivery_tag=method.delivery_tag)

# Connect to RabbitMQ
connection = pika.BlockingConnection(pika.ConnectionParameters(host=rabbitmq_host))
channel = connection.channel()

# Declare the queue in case it doesn't exist
channel.queue_declare(queue=rabbitmq_queue, durable=True)

# Start consuming the queue and use the callback function to process messages
channel.basic_consume(queue=rabbitmq_queue, on_message_callback=callback)

print('Waiting for messages from RabbitMQ...')
channel.start_consuming()
