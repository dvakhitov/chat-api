const axios = require('axios');

class AuthService {
    constructor(symfonyServer) {
        this.symfonyServer = symfonyServer;
    }

    async waitForServer() {
        while (true) {
            try {
                await axios.get(this.symfonyServer + '/api/health');
                console.log('Server is ready');
                break;
            } catch (error) {
                console.log('Waiting for server to be ready...');
                await new Promise(resolve => setTimeout(resolve, 5000));
            }
        }
    }

    async validateToken(token) {
        try {
            const response = await axios.post(
                `${this.symfonyServer}/api/auth/validate-token`,
                {},
                {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                }
            );

            return {
                success: response.status === 200,
                clientId: response.data.chatUserUuid,
                email: response.data.email,
                userId: response.data.userId,
                data: response.data
            };
        } catch (error) {
            console.error('Token validation error:', error.response?.data || error.message);
            return { success: false };
        }
    }
}

module.exports = AuthService; 