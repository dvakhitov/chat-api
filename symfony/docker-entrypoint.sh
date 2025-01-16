#!/bin/sh

echo 'Start entrypoint'
set -e

cp -r /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
echo -e "\033[0;32mSet localtime ${TZ} \033[0m"

if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if ! [ -d ./var/ ]; then
  mkdir var
  echo -e "\033[1;33mDir ./var created\033[0m"
fi


# Настраиваем права доступа
if command -v setfacl > /dev/null; then
  setfacl -R -m u:www-data:rwX -m u:$(whoami):rwX ./var
  setfacl -dR -m u:www-data:rwX -m u:$(whoami):rwX ./var
else
  echo "setfacl not found. Skipping ACL setup."
fi

#echo "Change owner"
#chown -R 33:33 ../

if [ -d "/var/www/symfony/my_project" ]; then
  echo "Directory /var/www/symfony/my_project already exists. Skipping setup..."
else

  # Ваша команда для настройки, например:
  symfony new my_project --webapp --version=7.2
fi

echo "Run PHP entrypoint"
exec "$@"

