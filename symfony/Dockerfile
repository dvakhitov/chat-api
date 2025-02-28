FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    librabbitmq-dev \
    libssl-dev \
    supervisor \
    libpq-dev \
    netcat-traditional \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    opcache \
    && pecl channel-update pecl.php.net \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && pecl install apcu \
    && docker-php-ext-enable apcu


# Настройка PHP
RUN echo "memory_limit = 2G" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "max_execution_time = 600" >> /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "date.timezone = UTC" >> /usr/local/etc/php/conf.d/memory-limit.ini

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Сделаем composer глобально доступным
RUN ln -s /usr/bin/composer /usr/local/bin/composer

# Set working directory
WORKDIR /var/www

# Copy supervisor configuration
COPY docker/supervisor/messenger-worker.conf /etc/supervisor/conf.d/
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d
COPY docker/supervisor/php-fpm.conf /etc/supervisor/conf.d/
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Set correct permissions
RUN chmod 644 /etc/supervisor/conf.d/supervisord.conf \
    && chmod 644 /etc/supervisor/conf.d/messenger-worker.conf \
    && chmod 644 /etc/supervisor/conf.d/php-fpm.conf \
    && chmod 644 /usr/local/etc/php-fpm.d/www.conf

# Create necessary directories and set permissions
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/run \
    && chmod 777 /var/run

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy existing application directory contents
COPY . /var/www/symfony
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
# Create public directory if it doesn't exist
RUN mkdir -p /var/www/symfony/public

WORKDIR /var/www/symfony

# Install dependencies
RUN php -d memory_limit=-1 /usr/bin/composer install

# Change ownership of our applications
RUN chown -R www-data:www-data /var/www/symfony

# Expose port 9000
EXPOSE 9000

USER root
# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
