#!/usr/bin/env bash
set -e

# Permettre à Composer de s'exécuter en tant que root
export COMPOSER_ALLOW_SUPERUSER=1

# Copier le fichier d'environnement
cp .env.railway .env

# Installer les dépendances PHP avec les bonnes options
composer install --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts

# Créer le répertoire de la base de données avec les bonnes permissions
mkdir -p /app/database
touch /app/database/database.sqlite
chmod -R 777 /app/database

# Créer le répertoire de stockage avec les bonnes permissions
mkdir -p /app/storage
chmod -R 777 /app/storage

# Générer la clé d'application manuellement si elle n'existe pas
if [ -z "$APP_KEY" ]; then
    php -r "echo 'base64:'.base64_encode(random_bytes(32));" > .env.key
    export APP_KEY=$(cat .env.key)
    echo "APP_KEY=$APP_KEY" >> .env
    rm .env.key
fi

# Créer le lien symbolique pour le stockage
php artisan storage:link --force

# Installer les dépendances Node.js
npm ci --ignore-scripts

# Construire les assets
npm run build

# Exécuter les migrations
php artisan migrate --force
