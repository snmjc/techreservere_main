#!/bin/sh
set -e

# Always install/update PHP dependencies on container start so that
# sync+restart from docker compose watch picks up composer.json changes
# without needing a full image rebuild.
if [ -f /app/composer.json ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Start PHP built-in server for development
exec php -S 0.0.0.0:8000 -t public public/index.php
