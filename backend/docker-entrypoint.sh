#!/bin/sh
set -e

# Install PHP dependencies only when vendor is completely missing
# --no-scripts skips the slow cache:clear auto-script
if [ ! -f /app/vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --no-scripts
fi

# Clear stale cache and warm prod cache so first request is fast
php bin/console cache:clear --no-warmup --env=prod 2>/dev/null || true
php bin/console cache:warmup --env=prod 2>/dev/null || true

# Start PHP built-in server
exec php -S 0.0.0.0:8000 -t public public/index.php
