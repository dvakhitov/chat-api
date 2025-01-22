const WebSocket = require('ws');
const axios = require('axios');
const http = require('http');

// Конфигурация
const PORT = process.env.WS_PORT || 6001;
const SYMFONY_SERVER = process.env.SYMFONY_SERVER || 'http://nginx:80';
const AUTH_TIMEOUT = 10000; // 10 секунд на аутентификацию
const MAX_RETRIES = 5;
const RETRY_DELAY = 5000;

// Создаем HTTP сервер для healthcheck
const httpServer = http.createServer((req, res) => {
    if (req.url === '/health') {
        res.writeHead(200);
        res.end('OK');
    } else {
        res.writeHead(404);
        res.end();
    }
});

// Настраиваем axios для повторных попыток
axios.defaults.retry = MAX_RETRIES;
axios.defaults.retryDelay = RETRY_DELAY;
axios.defaults.timeout = 5000;

// Добавляем перехватчик для повторных попытков
axios.interceptors.response.use(undefined, async (err) => {
    const config = err.config;
    if (!config || !config.retry) return Promise.reject(err);
    
    config.__retryCount = config.__retryCount || 0;
    
    if (config.__retryCount >= config.retry) {
        return Promise.reject(err);
    }
    
    config.__retryCount += 1;
    console.log(`Retry attempt ${config.__retryCount} for ${config.url}`);
    
    await new Promise(resolve => setTimeout(resolve, config.retryDelay));
    return axios(config);
});

// Храним подключенных клиентов
const clients = new Map();
// Храним временные подключения, ожидающие аутентификации
const pendingClients = new Map();

// Обработчик подключения
const wss = new WebSocket.Server({ server: httpServer });

// Проверяем доступность nginx перед запуском
async function waitForNginx() {
    while (true) {
        try {
            await axios.get(SYMFONY_SERVER + '/api/health');
            console.log('Nginx is ready');
            break;
        } catch (error) {
            console.log('Waiting for Nginx to be ready...');
            await new Promise(resolve => setTimeout(resolve, 5000));
        }
    }
}

// Ждем готовности nginx перед запуском сервера
waitForNginx().then(() => {
    httpServer.listen(PORT);
    console.log(`WebSocket server is running on ws://localhost:${PORT}`);
});

// Обработка входящих сообщений во время аутентификации
wss.on('connection', async (ws, req) => {
    const connectionId = Date.now().toString();
    let authTimeout;

    // Добавляем подключение в список ожидающих
    pendingClients.set(connectionId, {
        ws,
        connectedAt: Date.now()
    });

    // Устанавливаем таймер на аутентификацию
    authTimeout = setTimeout(() => {
        if (pendingClients.has(connectionId)) {
            ws.close(4001, 'Authentication timeout');
            pendingClients.delete(connectionId);
        }
    }, AUTH_TIMEOUT);

    // Обработка входящих сообщений во время аутентификации
    ws.on('message', async (data) => {
        try {
            // Если клиент уже аутентифицирован, используем обычный обработчик сообщений
            if (clients.has(ws)) {
                handleAuthenticatedMessage(ws, data);
                return;
            }

            // Пытаемся разобрать JSON с токеном
            const message = JSON.parse(data);
            
            if (!message.jwt) {
                ws.close(4002, 'No token provided');
                return;
            }

            // Проверяем токен через Symfony API
            try {
                const response = await axios.post(`${SYMFONY_SERVER}/api/auth/validate-token`, {}, {
                    headers: {
                        'Authorization': `Bearer ${message.jwt}`
                    }
                });

                if (response.status === 200) {
                    // Очищаем таймер аутентификации
                    clearTimeout(authTimeout);
                    
                    // Сохраняем информацию о клиенте
                    const clientId = response.data.chatUserUuid;
                    clients.set(clientId, {
                        ws,
                        token: message.jwt
                    });
                    
                    // Удаляем из списка ожидающих
                    pendingClients.delete(connectionId);
                    
                    // Отправляем ответ от сервера как есть
                    ws.send(JSON.stringify(response.data));
                    
                    console.log(`Client authenticated: ${clientId}`);
                } else {
                    ws.close(4003, 'Invalid token');
                }
            } catch (error) {
                console.error('Token validation error:', error.response?.data || error.message);
                ws.close(4004, 'Authentication failed');
            }
        } catch (error) {
            console.error('Message parsing error:', error);
            ws.close(4005, 'Invalid message format');
        }
    });

    // Обработка отключения во время ожидания аутентификации
    ws.on('close', () => {
        clearTimeout(authTimeout);
        pendingClients.delete(connectionId);
    });
});

// Настройка обработчиков для аутентифицированного клиента
function setupAuthenticatedClient(ws, clientId, userData) {
    // Обработка сообщений
    ws.on('message', async (data) => {
        try {
            const message = JSON.parse(data);

            // Проверяем токен перед каждым сообщением
            const clientData = clients.get(clientId);
            const isValid = await validateToken(clientData.jwt);
            if (!isValid) {
                ws.close(4001, 'Token expired');
                return;
            }

            // Отправляем сообщение в Symfony
            await sendToSymfony(message, clientId);

            switch (message.type) {
                case 'chat':
                    broadcast({
                        type: 'chat',
                        sender: {
                            id: clientId,
                            firstName: userData.firstName,
                            lastName: userData.lastName,
                            photoUrl: userData.photoUrl
                        },
                        message: message.text,
                        timestamp: new Date().toISOString()
                    });
                    break;

                case 'private':
                    sendToClient(message.to, {
                        type: 'private',
                        sender: {
                            id: clientId,
                            firstName: userData.firstName,
                            lastName: userData.lastName,
                            photoUrl: userData.photoUrl
                        },
                        message: message.text,
                        timestamp: new Date().toISOString()
                    });
                    break;

                default:
                    console.log('Unknown message type:', message.type);
            }
        } catch (error) {
            console.error('Error processing message:', error);
            ws.send(JSON.stringify({
                type: 'error',
                message: 'Error processing message'
            }));
        }
    });

    // Обработка отключения
    ws.on('close', () => {
        clients.delete(clientId);
        broadcast({
            type: 'user_disconnected',
            userId: clientId
        });
        console.log(`Client disconnected: ${clientId}`);
    });
}

// Функция проверки токена
async function validateToken(token) {
    try {
        const response = await axios.post(`${SYMFONY_SERVER}/api/auth/validate-token`, {}, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        return response.status === 200;
    } catch (error) {
        console.error('Token validation error:', error);
        return false;
    }
}

// Функция отправки сообщения всем клиентам
function broadcast(data) {
    clients.forEach((client) => {
        if (client.ws.readyState === WebSocket.OPEN) {
            client.ws.send(JSON.stringify(data));
        }
    });
}

// Функция отправки сообщения конкретному клиенту
function sendToClient(clientId, data) {
    const client = clients.get(clientId);
    if (client && client.ws.readyState === WebSocket.OPEN) {
        client.ws.send(JSON.stringify(data));
    }
}

// Функция отправки сообщения в Symfony
async function sendToSymfony(message, senderId) {
    try {
        const clientData = clients.get(senderId);
        const response = await axios.post(`${SYMFONY_SERVER}/api/messages`, {
            type: message.type,
            sender: senderId,
            recipient: message.to || 'all',
            message: message.text,
            timestamp: new Date().toISOString()
        }, {
            headers: {
                'Authorization': `Bearer ${clientData.jwt}`
            }
        });
        
        if (response.status !== 202) {
            throw new Error('Symfony server error');
        }
    } catch (error) {
        console.error('Error sending message to Symfony:', error);
        // Здесь можно добавить логику очереди для повторной отправки
    }
}