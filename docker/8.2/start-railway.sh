#!/bin/bash
set -e

echo "🚀 Starting deployment process..."

# Copier le fichier d'environnement
echo "📝 Setting up environment variables..."
cp .env.railway .env

# Générer la clé d'application si nécessaire
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# Créer le lien symbolique pour le stockage
echo "🔗 Creating storage link..."
php artisan storage:link --force

# Nettoyer les caches
echo "🧹 Clearing application cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimiser l'application
echo "⚡ Optimizing application..."
php artisan optimize

# Exécuter les migrations
echo "🔄 Running database migrations..."
php artisan migrate --force

# Configurer les permissions
echo "👮 Setting up permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/bootstrap/cache

echo "✅ Deployment process completed!"
echo "🌐 Starting Apache..."

# Démarrer Apache en premier plan
exec apache2-foreground
