FROM node:18-alpine

# Устанавливаем wget для healthcheck
RUN apk add --no-cache wget

# Устанавливаем рабочую директорию
WORKDIR /app

# Копируем package.json и package-lock.json
COPY package*.json ./

# Устанавливаем зависимости
RUN npm install

# Копируем исходный код
COPY . .

# Открываем порт
EXPOSE 6001

# Запускаем сервер
CMD ["node", "server.js"]
