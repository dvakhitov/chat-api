const axios = require('axios');

class MessageHandler {
    constructor(symfonyServer, clientManager) {
        this.symfonyServer = symfonyServer;
        this.clientManager = clientManager;
    }

    async handleAuthenticatedMessage(clientId, token, data) {
        const message = JSON.parse(data);
        const response = await this.sendToChatApp(message, clientId, token);
        return response;
    }

    async sendToChatApp(message, senderId, token) {
        try {
            const response = await axios.post(`${this.symfonyServer}/api/messages`, {
                type: message.type,
                sender: senderId,
                userId: this.clientManager.getAuthenticatedClientByWs(message.ws).userId,
                recipient: message.to || 'all',
                message: message.text,
                timestamp: new Date().toISOString()
            }, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            if (response.status !== 202) {
                throw new Error('Chat app server error');
            }

            return response.data;
        } catch (error) {
            console.error('Error sending message to chat app:', error);
            throw error;
        }
    }
}

module.exports = MessageHandler; 