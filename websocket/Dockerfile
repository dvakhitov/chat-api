FROM node:18

# Устанавливаем рабочую директорию
WORKDIR /app

# Копируем package.json и устанавливаем зависимости
COPY package.json ./
RUN npm install

# Копируем остальные файлы проекта
COPY . .

# Открываем порт для WebSocket
EXPOSE 6001

# Устанавливаем команду запуска
CMD ["node", "server.js"]
