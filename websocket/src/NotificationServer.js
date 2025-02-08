const WebSocket = require('ws');
const amqp = require('amqplib');

class NotificationServer {
    constructor(config) {
        this.config = config;
        this.clientManager = new Map(); // userId -> WebSocket
        this.setupWebSocket();
        this.setupRabbitMQ();
    }

    async setupRabbitMQ() {
        try {
            const connection = await amqp.connect(this.config.rabbitmq.url);
            const channel = await connection.createChannel();
            
            // Настраиваем exchange и очередь
            await channel.assertExchange('notifications', 'direct', { durable: true });
            await channel.assertQueue('notifications_queue', { durable: true });
            await channel.bindQueue('notifications_queue', 'notifications', '');
            
            // Слушаем сообщения
            await channel.consume('notifications_queue', async (msg) => {
                if (msg !== null) {
                    try {
                        await this.handleNotification(msg);
                        channel.ack(msg); // Подтверждаем успешную обработку
                    } catch (error) {
                        console.error('Error processing message:', error);
                        // В случае ошибки возвращаем сообщение в очередь
                        channel.nack(msg);
                    }
                }
            });

            console.log('Connected to RabbitMQ, listening for notifications');
        } catch (error) {
            console.error('RabbitMQ connection error:', error);
            process.exit(1);
        }
    }

    async handleNotification(msg) {
        const notification = JSON.parse(msg.content.toString());
        const { type, userId } = notification;

        // Получаем WebSocket клиента по userId
        const client = this.clientManager.get(userId);
        if (!client) {
            console.log(`No client found for user ${userId}`);
            return;
        }

        console.log('Received notification:', notification);
        // Отправляем уведомление в зависимости от типа
        switch (notification.constructor.name) {
            case 'SenderNotificationMessageDTO':
                client.send(JSON.stringify({
                    type: 'message_sent',
                    chatId: notification.chatId,
                    messageId: notification.messageId
                }));
                break;

            case 'RecipientNotificationMessageDTO':
                client.send(JSON.stringify({
                    type: 'new_message',
                    chatId: notification.chatId,
                    messageId: notification.messageId,
                    senderName: notification.senderName
                }));
                break;
        }
    }

    setupWebSocket() {
        this.wss = new WebSocket.Server({ port: this.config.port });

        this.wss.on('connection', (ws) => {
            ws.on('message', async (data) => {
                try {
                    const message = JSON.parse(data);
                    
                    // Аутентификация и сохранение связи userId -> ws
                    if (message.type === 'auth') {
                        this.clientManager.set(message.userId, ws);
                        ws.userId = message.userId;
                        ws.send(JSON.stringify({ type: 'auth', status: 'success' }));
                    }
                } catch (error) {
                    console.error('WebSocket message error:', error);
                }
            });

            ws.on('close', () => {
                if (ws.userId) {
                    this.clientManager.delete(ws.userId);
                }
            });
        });

        console.log(`WebSocket server running on port ${this.config.port}`);
    }
}

// Конфигурация
const config = {
    port: process.env.WS_PORT || 6001,
    rabbitmq: {
        url: `amqp://${process.env.RABBITMQ_USER}:${process.env.RABBITMQ_PASSWORD}@${process.env.RABBITMQ_HOST}:${process.env.RABBITMQ_PORT}/${process.env.RABBITMQ_VHOST}`
    }
};

new NotificationServer(config); 