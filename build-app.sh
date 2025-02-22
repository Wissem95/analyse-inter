#!/bin/bash
# Sortir si une commande échoue
set -e

# Installation des dépendances
composer install --no-dev --optimize-autoloader

# Construction des assets
npm ci
npm run build

# Optimisation de Laravel
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Migration de la base de données
php artisan migrate --force
