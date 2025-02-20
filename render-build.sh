#!/usr/bin/env bash
set -e

# Installation des dépendances PHP
composer install --no-dev --optimize-autoloader --no-interaction

# Installation des dépendances Node.js et build des assets
npm ci
npm run build

# Nettoyage du cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Migrations de la base de données
php artisan migrate --force

# Configuration des permissions
chmod -R 775 storage bootstrap/cache
