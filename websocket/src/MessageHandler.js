const axios = require('axios');

class MessageHandler {
    constructor(symfonyServer, clientManager) {
        this.symfonyServer = symfonyServer;
        this.clientManager = clientManager;
    }

    async handleAuthenticatedMessage(clientId, token, data, ws) {
        const message = JSON.parse(data);
        const response = await this.sendToChatApp(message, clientId, token, ws);
        return response;
    }

    async sendToChatApp(message, senderId, token, ws) {
        try {
            const clientInfo = this.clientManager.getClientByWs(ws);
            if (!clientInfo) {
                throw new Error('Client not authenticated');
            }

            message.senderId = clientInfo.userId;

            console.log('Sending message to chat app:', message);
            const response = await axios.post(`${this.symfonyServer}/api/messages`, message, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            if (response.status !== 202 && response.status !== 200) {
                throw new Error('Chat app server error');
            }

            return response.data;
        } catch (error) {
            console.error('Error sending message to chat app:', error.response ? error.response.data : error.message);
            throw error;
        }
    }
}

module.exports = MessageHandler; 