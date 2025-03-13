#!/bin/bash
set -e

# Ensure correct permissions
mkdir -p /var/www/symfony/var
chown -R www-data:www-data /var/www/symfony/var

echo "starting supervisor"
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
echo -e "\033[0;32mSupervisord started\033[0m"

echo "Run cron"
service cron start

echo "Run PHP entrypoint"
exec docker-php-entrypoint "$@"
