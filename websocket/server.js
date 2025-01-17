const WebSocket = require('ws');
const { v4: uuidv4 } = require('uuid');

// Порт для WebSocket-сервера
const PORT = 6001;

// Создаем WebSocket-сервер
const wss = new WebSocket.Server({ port: PORT });

console.log(`WebSocket server is running on ws://localhost:${PORT}`);

// Храним подключенных клиентов
const clients = new Map();

// Обработка нового подключения
wss.on('connection', (ws) => {
    // Уникальный идентификатор для клиента
    const clientId = uuidv4();
    clients.set(clientId, ws);

    console.log(`Client connected: ${clientId}`);

    // Сообщение клиенту о подключении
    ws.send(JSON.stringify({ type: 'welcome', clientId, message: 'Welcome to the chat!' }));

    // Обработка входящих сообщений от клиента
    ws.on('message', (data) => {
        try {
            const message = JSON.parse(data);

            switch (message.type) {
                case 'chat':
                    // Отправляем сообщение всем клиентам
                    broadcast({
                        type: 'chat',
                        sender: clientId,
                        message: message.text,
                    });
                    break;

                case 'private':
                    // Отправляем сообщение конкретному клиенту
                    sendToClient(message.to, {
                        type: 'private',
                        sender: clientId,
                        message: message.text,
                    });
                    break;

                default:
                    console.log('Unknown message type:', message.type);
            }
        } catch (error) {
            console.error('Error parsing message:', error);
        }
    });

    // Обработка закрытия соединения
    ws.on('close', () => {
        clients.delete(clientId);
        console.log(`Client disconnected: ${clientId}`);
    });
});

// Функция для отправки сообщения всем клиентам
function broadcast(data) {
    clients.forEach((client) => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(data));
        }
    });
}

// Функция для отправки сообщения конкретному клиенту
function sendToClient(clientId, data) {
    const client = clients.get(clientId);
    if (client && client.readyState === WebSocket.OPEN) {
        client.send(JSON.stringify(data));
    }
}
