const WebSocketServer = require('./src/WebSocketServer');
const config = require('./config');

console.log('Starting server');
const server = new WebSocketServer(config);

// Убедитесь, что строки ниже отсутствуют или закомментированы
server.start().catch(console.error);