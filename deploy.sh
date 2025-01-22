#!/bin/bash

# Остановка и удаление старых контейнеров
docker compose down

# Создаем директорию для секретов если её нет
mkdir -p secrets

# Создаем файлы с секретами если их нет
if [ ! -f secrets/db_password.txt ]; then
    echo "your_secure_db_password" > secrets/db_password.txt
fi

if [ ! -f secrets/rabbitmq_password.txt ]; then
    echo "your_secure_rabbitmq_password" > secrets/rabbitmq_password.txt
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
docker compose -f docker-compose.yaml -f docker-compose.prod.yaml up -d --build 