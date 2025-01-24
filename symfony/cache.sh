#!/bin/sh

php bin/console cache:pool:clear cache.global_clearer
rm -rf var/cache/*
rm -rf var/log/*
composer dump-autoload --classmap-authoritative --optimize
php bin/console cache:warmup
#php bin/console lint:container
touch var/log/dev.log && chmod ugo+rw var/log/dev.log
#bin/console cache:warmup --env=prod
