import pika

def on_message_received(ch, method, properties, body):
	print(f"received new message: {body}")

connection_parameters = pika.ConnectionParameters('localhost')

connection = pika.BlockingConnection(connection_parameters)

channel =  connection.channel()

channel.queue_declare(queue='products_add_to_odoo', durable=True)

channel.basic_consume(queue='products_add_to_odoo', auto_ack=True, on_message_callback=on_message_received)

print("Starting Consuming add product from WordPress")

channel.start_consuming()
