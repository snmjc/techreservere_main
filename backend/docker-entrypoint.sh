#!/bin/sh
set -e

# Install PHP dependencies only when vendor is completely missing
# --no-scripts skips the slow cache:clear auto-script; Symfony warms cache on first request
if [ ! -f /app/vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --no-scripts
fi

# Start PHP built-in server
exec php -S 0.0.0.0:8000 -t public public/index.php
