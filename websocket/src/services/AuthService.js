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
        console.log('validateToken called with token:', token);
        try {
            console.log('Token validation start');
            const response = await axios.post(`${this.symfonyServer}/api/auth/validate-token`, {}, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            console.log('Token validation response:', response.status, response.data);

            // Возвращаем ответ от сервиса приложения как есть
            return response.data;
        } catch (error) {
            console.error('Token validation error:', error.response ? error.response.data : error.message);
            // Выбрасываем ошибку для обработки выше
            throw error;
        }
    }
}

module.exports = AuthService; 