#!/usr/bin/env sh
set -e
cd /var/www/html

# Ensure .env exists
[ -f .env ] || cp .env.example .env || true

# Generate app key if missing
if ! php -r 'exit((bool)env("APP_KEY"));'; then
  echo "==> Generating app key"
  php artisan key:generate || true
fi

# Optional DB setup
if [ "${RUN_MIGRATIONS}" = "true" ]; then
  HOST="${DB_HOST:-mysql}"; PORT="${DB_PORT:-3306}"
  echo "==> Waiting for MySQL @ ${HOST}:${PORT} ..."
  until php -r '
    $h=getenv("DB_HOST")?: "mysql";
    $p=(int)(getenv("DB_PORT")?:3306);
    $t=@fsockopen($h,$p,$e1,$e2,2);
    if($t){fclose($t); exit(0);} exit(1);
  '; do
    echo " MySQL not ready yet, retrying..."
    sleep 2
  done
  echo "==> Running migrations"
  php artisan migrate --force || true
fi

if [ "${RUN_SEED}" = "true" ]; then
  echo "==> Seeding database"
  php artisan db:seed --class=BulkSeeder --force || true
fi

exec "$@"
