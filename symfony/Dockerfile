FROM php:8.4-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_pgsql

RUN apt-get update && apt-get install -y acl

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/symfony

# Установка прав для разработки
RUN usermod -u 1000 www-data && chown -R www-data:www-data /var/www && chmod -R gu+rxw ./

USER www-data

# Команда по умолчанию
CMD ["php-fpm"]
