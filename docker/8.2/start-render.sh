#!/bin/bash
set -e

echo "🚀 Démarrage du processus de déploiement..."

# Configuration de l'URL de la base de données Neon
export DATABASE_URL="postgresql://neondb_owner:npg_wB9xK2dDjSWm@ep-tiny-cell-a8nfck52-pooler.eastus2.azure.neon.tech/neondb?sslmode=require"

# Vérification du contenu du fichier .env
echo "📄 Contenu du fichier .env:"
cat .env

# Configuration des permissions avant tout
echo "👮 Configuration des permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/bootstrap/cache

# Nettoyage du cache Laravel (sans utiliser la base de données)
echo "🧹 Nettoyage du cache Laravel..."
rm -rf /var/www/html/bootstrap/cache/*.php
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Test de connexion à la base de données
echo "⏳ Test de connexion à la base de données..."
until php artisan db:monitor > /dev/null 2>&1; do
    echo "Attente de la base de données..."
    sleep 2
done
echo "✅ Connexion à la base de données établie!"

# Nettoyage des tables existantes
echo "🔄 Nettoyage de la base de données..."
php artisan db:wipe --force || true

# Création de la table de migrations si elle n'existe pas
echo "🔄 Préparation de la base de données..."
PGPASSWORD=npg_wB9xK2dDjSWm psql "postgresql://neondb_owner@ep-tiny-cell-a8nfck52-pooler.eastus2.azure.neon.tech/neondb?sslmode=require" -c 'CREATE TABLE IF NOT EXISTS migrations (id SERIAL PRIMARY KEY, migration VARCHAR(255) NOT NULL, batch INTEGER NOT NULL);' || true

# Migrations avec plus de verbosité
echo "🔄 Exécution des migrations..."
php artisan migrate:fresh --force -v

# Optimisation pour la production
echo "⚡ Optimisation de l'application..."
php artisan optimize
php artisan view:cache
php artisan config:cache
php artisan route:cache

echo "✅ Déploiement terminé!"
echo "🌐 Démarrage d'Apache..."

# Démarrage d'Apache en premier plan
apache2-foreground
