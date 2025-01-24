const WebSocketServer = require('./src/WebSocketServer');

const config = {
    port: process.env.WS_PORT || 6001,
    domain: process.env.DOMAIN || 'localhost',
    symfonyServer: process.env.SYMFONY_SERVER || 'http://nginx:80',
    authTimeout: 10000
};

const server = new WebSocketServer(config);
server.start().catch(console.error);