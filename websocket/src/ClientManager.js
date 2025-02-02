class ClientManager {
    constructor() {
        this.clients = new Map(); // userId -> WebSocket
        this.pendingClients = new Map(); // connectionId -> WebSocket
    }

    addClient(userId, ws) {
        this.clients.set(userId.toString(), ws);
        console.log(`Client added with userId: ${userId.toString()}`);
    }

    addPendingClient(connectionId, ws) {
        this.pendingClients.set(connectionId, ws);
    }

    removePendingClient(connectionId) {
        this.pendingClients.delete(connectionId);
    }

    hasPendingClient(connectionId) {
        return this.pendingClients.has(connectionId);
    }

    authenticateClient(connectionId, ws, userId, token) {
        this.pendingClients.delete(connectionId);
        ws.userId = userId.toString();
        ws.token = token;
        this.clients.set(ws.userId, ws);
        console.log(`Client authenticated: User ID ${ws.userId}`);
    }

    removeClient(userId) {
        this.clients.delete(userId.toString());
        console.log(`Client removed with userId: ${userId.toString()}`);
    }

    getClientByWs(ws) {
        for (const [userId, clientWs] of this.clients.entries()) {
            if (clientWs === ws) {
                return { userId, ws: clientWs };
            }
        }
        return null;
    }

    getClient(userId) {
        console.log(`Attempting to get client with userId: ${userId.toString()}`);
        return this.clients.get(userId.toString());
    }

    isClientAuthenticated(ws) {
        return ws.userId && this.clients.has(ws.userId);
    }

    getAuthenticatedClientByWs(ws) {
        for (const [userId, clientWs] of this.clients.entries()) {
            if (clientWs === ws) {
                return {
                    userId: userId,
                    token: ws.token // Если нужно, можете добавить другие данные
                };
            }
        }
        return null;
    }

    getConnectedClients() {
        return Array.from(this.clients.keys());
    }
}

module.exports = ClientManager;