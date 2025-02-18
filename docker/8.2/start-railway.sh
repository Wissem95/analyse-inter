#!/bin/bash
set -e

echo "🚀 Démarrage du processus de déploiement..."

# Configuration de l'environnement
echo "📝 Configuration de l'environnement..."
cp .env.railway .env

# Vérification de la configuration
echo "🔍 Vérification de la configuration..."
php artisan config:clear
php artisan cache:clear

# Affichage des variables de connexion pour debug
echo "📊 Configuration de la base de données :"
echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"

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

# Test de connexion avec PHP
echo "🔌 Test de connexion à la base de données..."
php artisan db:monitor

# Génération de la clé si nécessaire
if [ -z "$APP_KEY" ]; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate
fi

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
