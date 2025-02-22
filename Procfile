web: cp .env.railway .env && php artisan config:cache && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
release: php artisan migrate --force --no-interaction
