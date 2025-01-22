#!/bin/bash

# Остановка и удаление старых контейнеров
docker compose down

# Создаем директорию для секретов если её нет
mkdir -p secrets

# Создаем файлы с секретами если их нет
if [ ! -f secrets/db_password.txt ]; then
    echo "$POSTGRES_PASSWORD" > secrets/db_password.txt
fi

if [ ! -f secrets/rabbitmq_password.txt ]; then
    echo "$RABBITMQ_PASSWORD" > secrets/rabbitmq_password.txt
fi

# Проверяем наличие SSL сертификатов
if [ ! -f nginx/ssl/fullchain.pem ] || [ ! -f nginx/ssl/privkey.pem ]; then
    echo "Error: SSL certificates not found in nginx/ssl/"
    echo "Please add fullchain.pem and privkey.pem to nginx/ssl/ directory"
    exit 1
fi

# Pull последних изменений из git
git pull origin main

# Установка зависимостей Composer в prod режиме
docker compose run --rm app composer install --no-dev --optimize-autoloader

# Очистка и прогрев кэша
docker compose run --rm app php bin/console cache:clear --env=prod
docker compose run --rm app php bin/console cache:warmup --env=prod

# Применение миграций
docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# Запуск новых контейнеров
#docker compose -f compose.yaml -f docker-compose.prod.yaml up -d --build
docker compose -f compose.yaml up -d --build 

# Проверяем наличие необходимых переменных окружения
if [ -z "$DOMAIN" ]; then
    echo "Error: DOMAIN environment variable is not set"
    echo "Usage: DOMAIN=example.com ./deploy.sh"
    exit 1
fi