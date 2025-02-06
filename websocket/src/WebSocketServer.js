const WebSocket = require('ws');
const http = require('http');
const ClientManager = require('./ClientManager');
const MessageHandler = require('./MessageHandler');
const AuthService = require('./services/AuthService');
const tenMinutes = 600000;

class WebSocketServer {
    constructor(config) {
        this.config = config;
        this.clientManager = new ClientManager();
        this.authService = new AuthService(config.symfonyServer);
        this.messageHandler = new MessageHandler(this.config.symfonyServer, this.clientManager);
        
        this.setupHttpServer();
        this.setupWebSocketServer();
        console.log('WebSocketServer constructor finished');
    }

    setupWebSocketServer() {
        const wsServer = http.createServer();
        this.wss = new WebSocket.Server({ server: wsServer });
        wsServer.listen(this.config.port, () => {
            console.log(`WebSocket server is running on port ${this.config.port}`);
        });

        this.wss.on('connection', this.handleConnection.bind(this));
    }

    handleConnection(ws) {
        const connectionId = Date.now().toString();
        ws.connectionId = connectionId;
        this.clientManager.addPendingClient(connectionId, ws);

        // Устанавливаем таймер ожидания аутентификации (например, 10 минут)
        const authTimeout = setTimeout(() => {
            if (this.clientManager.hasPendingClient(connectionId)) {
                ws.close(4001, 'Authentication timeout');
                this.clientManager.removePendingClient(connectionId);
            }
        }, this.config.authTimeout || tenMinutes); 

        ws.on('message', async (data) => {
            console.log('Received data:', data);
            try {
                const message = JSON.parse(data);
                // Заменяем 'token' на 'jwt' в полученном сообщении
                if (message.jwt) {
                    message.token = message.jwt;
                }
                
                // Проверяем, аутентифицирован ли клиент
                if (!this.clientManager.isClientAuthenticated(ws)) {
                    // Если не аутентифицирован, инициируем процесс аутентификации
                    await this.handleAuthenticationMessage(ws, data, connectionId, authTimeout);
                    return;
                }
                
                // Если клиент аутентифицирован, обрабатываем сообщение
                await this.handleMessage(ws, data);
            } catch (error) {
                console.error('WebSocket message error:', error);
            }
        });

        ws.on('close', () => {
            clearTimeout(authTimeout);
            if (ws.userId) {
                this.clientManager.removeClient(ws.userId);
            } else {
                this.clientManager.removePendingClient(ws.connectionId);
            }
        });
    }

    async handleAuthenticationMessage(ws, data, connectionId, authTimeout) {
        console.log('handleAuthenticationMessage start');

        let message;
        try {
            console.log('Received raw data:', data);
            message = JSON.parse(data);
            console.log('Parsed message:', message);

            // Замена 'jwt' на 'token'
            if (message.jwt) {
                message.token = message.jwt;
            }
        } catch (error) {
            console.error('JSON parsing error:', error);
            ws.send(JSON.stringify({ error: 'Invalid JSON format' }));
            ws.close(4005, 'Invalid JSON format');
            return;
        }

        if (!message.token) {
            ws.send(JSON.stringify({ error: 'No token provided' }));
            ws.close(4002, 'No token provided');
            return;
        }
        console.log('Token received:', message.token);

        try {
            const userData = await this.authService.validateToken(message.token);
            console.log('User data:', userData);
            if (userData) {
                const userId = userData.userId.toString();
                clearTimeout(authTimeout);
                this.clientManager.authenticateClient(
                    connectionId,
                    ws,
                    userId,
                    message.token
                );
                ws.userId = userId;
                ws.token = message.token;
                ws.send(JSON.stringify({ connected: 'true', countNotifications: 2, countChats: 0 }));

                console.log(`Client authenticated: User ID ${ws.userId}`);
            } else {
                ws.send(JSON.stringify({ error: 'Invalid token' }));
                ws.close(4003, 'Invalid token');
            }
        } catch (error) {
            console.error('Authentication error:', error);
            ws.send(JSON.stringify({ error: 'Authentication failed' }));
            ws.close(4004, 'Authentication failed');
        }
    }

    async handleMessage(ws, data) {
        const authenticatedClient = this.clientManager.getClientByWs(ws);
        
        if (!authenticatedClient) {
            ws.send(JSON.stringify({ error: 'Unauthorized' }));
            return;
        }

        const response = await this.messageHandler.handleAuthenticatedMessage(
            authenticatedClient.userId,
            ws.token,
            data,
            ws
        );
        // Вы можете обработать `response` по необходимости
    }

    setupHttpServer() {
        const express = require('express');
        const app = express();
        app.use(express.json());

        // Добавляем CORS middleware
        app.use((req, res, next) => {
            res.header('Access-Control-Allow-Origin', '*');
            res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            res.header('Access-Control-Allow-Headers', 'Content-Type');
            next();
        });

        // Добавляем health check endpoint
        app.get('/health', (req, res) => {
            res.json({ status: 'OK' });
        });

        app.post('/send', (req, res) => {
            const { recipient, data, type } = req.body;
            console.log(`Received send request to recipient: ${recipient}`);

            // Получаем список подключенных клиентов
            const connectedClients = this.clientManager.getConnectedClients();
            console.log('Connected clients:', connectedClients);

            // Получаем сокет клиента по userId
            const ws = this.clientManager.getClient(recipient);

            if (ws) {
                // Если необходимо обрезать сообщение и убрать 'data', то объединяем 'type' и 'data' в один объект
                const messageToSend = {
                    type,
                    ...data // Распаковываем поля из 'data' на верхний уровень
                };

                // Отправляем сообщение клиенту
                ws.send(JSON.stringify(messageToSend));

                res.json({ status: 'sent' });
            } else {
                console.log(`Client with userId ${recipient} not found.`);
                res.status(404).json({ error: 'Client not found' });
            }
        });

        // Запускаем HTTP сервер на порту 3001
        app.listen(3001, () => {
            console.log('HTTP server is running on port 3001');
        });
    }

    async start() {
        await this.authService.waitForServer();
    }
}

module.exports = WebSocketServer; 