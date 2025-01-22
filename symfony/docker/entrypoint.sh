#!/bin/bash
set -e

# Wait for RabbitMQ
until nc -z rabbitmq 5672; do
  echo "Waiting for RabbitMQ to be ready..."
  sleep 5
done

# Ensure correct permissions
chown -R www-data:www-data /var/www/symfony/var


# Start supervisor in foreground
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf

# Start PHP-FPM
exec php-fpm 