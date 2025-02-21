web: cp .env.railway .env && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan storage:link && php artisan migrate --force && php -S 0.0.0.0:$PORT -t public
release: php artisan migrate --force
