#!/bin/bash
set -e

# Copier le fichier d'environnement si nécessaire
if [ -f ".env.railway" ]; then
    cp .env.railway .env
fi

# Générer la clé d'application si nécessaire
if [ -z "$APP_KEY" ]; then
    php artisan key:generate
fi

# Créer le lien symbolique pour le stockage
php artisan storage:link --force

# Exécuter les migrations
php artisan migrate --force

# Optimiser l'application
php artisan optimize

# Exécuter la commande passée au conteneur
exec "$@"
