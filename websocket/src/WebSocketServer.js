const WebSocket = require('ws');
const http = require('http');
const ClientManager = require('./ClientManager');
const MessageHandler = require('./MessageHandler');
const AuthService = require('./services/AuthService');

class WebSocketServer {
    constructor(config) {
        this.config = config;
        this.clientManager = new ClientManager();
        this.authService = new AuthService(config.symfonyServer);
        this.messageHandler = new MessageHandler(config.symfonyServer);
        
        // Создаем отдельные серверы для HTTP и WebSocket
        this.setupHttpServer();
        this.setupWebSocketServer();
    }

    setupWebSocketServer() {
        const wsServer = http.createServer();
        this.wss = new WebSocket.Server({ server: wsServer });
        wsServer.listen(this.config.port, () => {
            console.log(`WebSocket server is running on port ${this.config.port}`);
        });
    }

    async start() {
        await this.authService.waitForServer();
        
        this.wss.on('connection', this.handleConnection.bind(this));
    }

    handleConnection(ws) {
        const connectionId = Date.now().toString();
        const authTimeout = setTimeout(() => {
            if (this.clientManager.hasPendingClient(connectionId)) {
                ws.close(4001, 'Authentication timeout');
                this.clientManager.removePendingClient(connectionId);
            }
        }, this.config.authTimeout);

        this.clientManager.addPendingClient(connectionId, ws);

        ws.on('message', async (data) => {
            await this.handleMessage(ws, data, connectionId, authTimeout);
        });

        ws.on('close', () => {
            clearTimeout(authTimeout);
            this.clientManager.removePendingClient(connectionId);
            this.clientManager.removeAuthenticatedClient(ws);
        });
    }

    async handleMessage(ws, data, connectionId, authTimeout) {
        try {
            const authenticatedClient = this.clientManager.getAuthenticatedClientByWs(ws);
            
            if (authenticatedClient) {
                const response = await this.messageHandler.handleAuthenticatedMessage(
                    authenticatedClient.id,
                    authenticatedClient.token,
                    data
                );
                ws.send(JSON.stringify(response));
                return;
            }

            await this.handleAuthenticationMessage(ws, data, connectionId, authTimeout);
        } catch (error) {
            console.error('Message handling error:', error);
            ws.send(JSON.stringify({
                status: 'error',
                message: 'Failed to process message'
            }));
        }
    }

    async handleAuthenticationMessage(ws, data, connectionId, authTimeout) {
        const message = JSON.parse(data);
        
        if (!message.jwt) {
            ws.close(4002, 'No token provided');
            return;
        }

        try {
            const authResult = await this.authService.validateToken(message.jwt);
            
            if (authResult.success) {
                clearTimeout(authTimeout);
                this.clientManager.authenticateClient(
                    connectionId,
                    ws,
                    authResult.clientId,
                    message.jwt,
                    authResult.email,
                    authResult.userId
                );
                ws.send(JSON.stringify(authResult.data));

                console.log(`Client authenticated: ${authResult.email} (ID: ${authResult.userId})`);
            } else {
                ws.close(4003, 'Invalid token');
            }
        } catch (error) {
            console.error('Authentication error:', error);
            ws.close(4004, 'Authentication failed');
        }
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
            const clientId = this.clientManager.getClientIdByUserId(recipient);

            if (clientId && this.clientManager.authenticatedClients.has(clientId)) {
                const ws = this.clientManager.authenticatedClients.get(clientId);
                ws.send(JSON.stringify({ type, data }));
                res.json({ status: 'sent' });
            } else {
                res.status(404).json({ error: 'Client not found' });
            }
        });

        // Запускаем HTTP сервер на порту 3001
        app.listen(3001, () => {
            console.log('HTTP server is running on port 3001');
        });
    }
}

module.exports = WebSocketServer; 