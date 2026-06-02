#!/bin/sh
set -e

# Symfony Runtime (symfony/dotenv) expects /app/.env to exist in dev.
# In Docker dev we prefer container environment variables; generate a minimal /app/.env if missing.
if [ ! -f /app/.env ]; then
  if [ -f /app/example.env ]; then
    cp /app/example.env /app/.env
  else
    cat > /app/.env <<EOF
APP_ENV=${APP_ENV:-dev}
APP_DEBUG=${APP_DEBUG:-1}
DATABASE_URL=${DATABASE_URL:-}
POSTGRES_HOST=${POSTGRES_HOST:-database}
POSTGRES_DB=${POSTGRES_DB:-techreserve}
POSTGRES_USER=${POSTGRES_USER:-techreserve_user}
POSTGRES_PASSWORD=${POSTGRES_PASSWORD:-techreserve_pass}
CLERK_SECRET_KEY=${CLERK_SECRET_KEY:-}
CLERK_API_BASE_URL=${CLERK_API_BASE_URL:-https://api.clerk.com}
CLERK_JWT_ISSUER=${CLERK_JWT_ISSUER:-}
DEFAULT_URI=${DEFAULT_URI:-https://topic-recorded-listprice-verde.trycloudflare.com}
FRONTEND_URL=${FRONTEND_URL:-https://topic-recorded-listprice-verde.trycloudflare.com}
EOF
  fi
fi

# Install PHP dependencies only when vendor is completely missing
# --no-scripts skips the slow cache:clear auto-script
if [ ! -f /app/vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --no-scripts
fi

# Clear stale cache and warm prod cache so first request is fast
php bin/console cache:clear --no-warmup --env=prod 2>/dev/null || true
php bin/console cache:warmup --env=prod 2>/dev/null || true

# In dev containers, ensure the database schema is up to date.
# This avoids 500s like "relation \"reservations\" does not exist" when migrations haven't been applied yet.
if [ "${APP_ENV:-dev}" != "prod" ]; then
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>/dev/null || true
fi

# Start PHP built-in server
exec php -S 0.0.0.0:8000 -t public public/index.php
