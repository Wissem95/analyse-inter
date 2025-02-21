web: cp .env.railway .env && \
    composer install --no-dev --optimize-autoloader && \
    php artisan key:generate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan migrate --force --no-interaction && \
    php -S 0.0.0.0:$PORT -t public
release: php artisan migrate --force --no-interaction
