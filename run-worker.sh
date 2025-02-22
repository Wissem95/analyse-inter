#!/bin/bash
# Sortir si une commande échoue
set -e

# Configuration de la file d'attente
export QUEUE_CONNECTION=database

# Démarrer le worker avec supervision
php artisan queue:work --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 --max-time=3600
