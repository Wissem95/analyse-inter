#!/bin/bash
set -e

echo "🚀 Démarrage du processus de déploiement..."

# Nettoyage du cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Attente de la base de données
echo "⏳ Attente de la base de données..."
max_tries=30
count=0
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME"; do
    echo "PostgreSQL n'est pas prêt - nouvelle tentative dans 1 seconde"
    sleep 1
    count=$((count + 1))
    if [ $count -ge $max_tries ]; then
        echo "❌ Impossible de se connecter à PostgreSQL après $max_tries tentatives"
        exit 1
    fi
done

# Génération de la clé si nécessaire
if [ -z "$APP_KEY" ]; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate --force
fi

# Migrations avec plus de verbosité
echo "🔄 Exécution des migrations..."
php artisan migrate --force -v

# Configuration des permissions
echo "👮 Configuration des permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/bootstrap/cache

echo "✅ Déploiement terminé!"
echo "🌐 Démarrage d'Apache..."

# Démarrage d'Apache en premier plan
apache2-foreground
