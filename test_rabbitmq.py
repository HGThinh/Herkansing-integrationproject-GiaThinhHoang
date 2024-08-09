import pika

def test_connection():
    connection_params = pika.ConnectionParameters(
        host='rabbitmq',
        port=5672,
        credentials=pika.PlainCredentials('guest', 'guest')
    )

    try:
        connection = pika.BlockingConnection(connection_params)
        channel = connection.channel()
        print("Connected to RabbitMQ successfully!")
        connection.close()
    except Exception as e:
        print(f"Failed to connect to RabbitMQ: {e}")

if __name__ == "__main__":
    test_connection()

