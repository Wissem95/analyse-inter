#!/bin/bash
# Sortir si une commande échoue
set -e

while true
do
    # Exécuter le scheduler Laravel
    php artisan schedule:run --verbose --no-interaction &

    # Nettoyer le cache si nécessaire
    if [ $((RANDOM % 100)) -lt 10 ]; then
        php artisan cache:prune-stale-tags
    fi

    sleep 60
done
