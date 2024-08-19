import pika

def callback(ch, method, properties, body):
    print(f"Received {body}")

connection = pika.BlockingConnection(pika.ConnectionParameters('localhost'))
channel = connection.channel()
channel.queue_declare(queue='pos_orders_test')

channel.basic_consume(queue='pos_orders_test', on_message_callback=callback, auto_ack=True)
print('Waiting for messages...')
channel.start_consuming()
