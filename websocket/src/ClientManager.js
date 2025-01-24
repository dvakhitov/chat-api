class ClientManager {
    constructor() {
        this.pendingClients = new Map();
        this.authenticatedClients = new Map();
        this.emailToClientId = new Map();
        this.userIdToClientId = new Map();
    }

    addPendingClient(connectionId, ws) {
        this.pendingClients.set(connectionId, { ws, connectedAt: Date.now() });
    }

    removePendingClient(connectionId) {
        this.pendingClients.delete(connectionId);
    }

    hasPendingClient(connectionId) {
        return this.pendingClients.has(connectionId);
    }

    authenticateClient(connectionId, ws, clientId, token, email, userId) {
        this.authenticatedClients.set(clientId, { 
            ws, 
            token,
            email,
            userId,
            connectionId 
        });
        this.emailToClientId.set(email, clientId);
        this.userIdToClientId.set(userId, clientId);
        this.removePendingClient(connectionId);
    }

    removeAuthenticatedClient(ws) {
        for (const [clientId, client] of this.authenticatedClients.entries()) {
            if (client.ws === ws) {
                this.emailToClientId.delete(client.email);
                this.userIdToClientId.delete(client.userId);
                this.authenticatedClients.delete(clientId);
                break;
            }
        }
    }

    getClientIdByEmail(email) {
        return this.emailToClientId.get(email);
    }

    getAuthenticatedClientByWs(ws) {
        for (const [clientId, client] of this.authenticatedClients.entries()) {
            if (client.ws === ws) {
                return { id: clientId, ...client };
            }
        }
        return null;
    }

    getClientIdByUserId(userId) {
        return this.userIdToClientId.get(userId);
    }
}

module.exports = ClientManager; 