#!/bin/bash
set -e

echo "🚀 Démarrage du processus de déploiement..."

# Configuration de l'environnement
echo "📝 Configuration de l'environnement..."
cp .env.railway .env

# Attente de la base de données
echo "⏳ Attente de la base de données..."
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME"; do
  echo "PostgreSQL n'est pas prêt - nouvelle tentative dans 1 seconde"
  sleep 1
done

# Génération de la clé si nécessaire
if [ -z "$APP_KEY" ]; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate
fi

# Optimisations
echo "⚡ Optimisation de l'application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrations
echo "🔄 Exécution des migrations..."
php artisan migrate --force

# Configuration des permissions
echo "👮 Configuration des permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/bootstrap/cache

echo "✅ Déploiement terminé!"
echo "🌐 Démarrage d'Apache..."

# Démarrage d'Apache en premier plan
exec apache2-foreground
