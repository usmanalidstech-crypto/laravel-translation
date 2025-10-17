#!/usr/bin/env sh
set -e
echo "==> Waiting for MySQL @ ${DB_HOST:-mysql}:${DB_PORT:-3306} ..."
until php -r '
$h=getenv("DB_HOST")?: "mysql";
$p=(int)(getenv("DB_PORT")?:3306);
$t = @fsockopen($h,$p,$e1,$e2,2);
if($t){fclose($t); exit(0);} exit(1);
'; do
  echo " MySQL not ready yet, retrying..."
  sleep 2
done
echo "==> Running artisan key:generate (idempotent)"
php artisan key:generate || true
echo "==> Running migrations + seed"
php artisan migrate --force
php artisan db:seed --class=BulkSeeder --force
echo "==> Creating initial API token (also saved to storage/app/INIT_TOKEN.txt)"
php artisan tms:token "Local dev" | tee storage/app/INIT_TOKEN.txt || true
echo "==> Done."
