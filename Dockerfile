FROM node:18-alpine

WORKDIR /app

# Копируем package.json и package-lock.json
COPY package*.json ./

# Устанавливаем зависимости
RUN npm install

# Копируем весь остальной код
COPY . .

# Указываем порт
EXPOSE 6001

# Запускаем приложение
CMD [ "node", "server.js" ] 