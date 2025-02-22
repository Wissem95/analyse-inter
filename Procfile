web: cp .env.railway .env && \
    composer dump-autoload --optimize && \
    php artisan optimize:clear && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan storage:link && \
    php artisan migrate --force --no-interaction && \
    php -S 0.0.0.0:${PORT:-8000} -t public

release: php artisan migrate --force --no-interaction
