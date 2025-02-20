#!/bin/bash
set -e

echo "🚀 Démarrage du processus de déploiement..."

# Vérification du contenu du fichier .env
echo "📄 Contenu du fichier .env:"
cat .env

# Nettoyage du cache
echo "🧹 Nettoyage du cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Test de connexion à la base de données
echo "⏳ Test de connexion à la base de données..."
echo "Tentative de connexion à PostgreSQL sur ep-tiny-cell-a8nfck52-pooler.eastus2.azure.neon.tech:5432"

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
