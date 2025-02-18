#!/bin/bash
set -e

echo "🚀 Démarrage du processus de déploiement..."

# Configuration de l'environnement
echo "📝 Configuration de l'environnement..."
cp .env.railway .env

# Vérification des variables requises
echo "🔍 Vérification des variables d'environnement..."
required_vars=(
    "DB_CONNECTION"
    "DB_HOST"
    "DB_PORT"
    "DB_DATABASE"
    "DB_USERNAME"
    "DB_PASSWORD"
)

for var in "${required_vars[@]}"; do
    if [ -z "${!var}" ]; then
        echo "❌ Erreur: La variable $var n'est pas définie"
        exit 1
    fi
done

# Affichage des variables de connexion pour debug
echo "📊 Configuration de la base de données :"
echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"

# Nettoyage du cache
echo "🧹 Nettoyage du cache..."
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

# Test de connexion avec PHP
echo "🔌 Test de connexion à la base de données..."
php artisan db:show

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
