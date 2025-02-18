#!/bin/bash
set -e

echo "🚀 Démarrage du processus de déploiement..."

# Configuration de l'environnement
echo "📝 Configuration de l'environnement..."
cp .env.railway .env

# Extraction des informations de connexion depuis DATABASE_URL
if [ ! -z "$DATABASE_URL" ]; then
    echo "🔄 Configuration de la base de données..."
    # Exemple de DATABASE_URL: postgres://user:password@host:port/dbname
    DB_HOST=$(echo $DATABASE_URL | awk -F[@//] '{print $4}' | cut -d: -f1)
    DB_PORT=$(echo $DATABASE_URL | awk -F[@//] '{print $4}' | cut -d: -f2 | cut -d/ -f1)
    DB_DATABASE=$(echo $DATABASE_URL | awk -F[@//] '{print $4}' | cut -d/ -f2)
    DB_USERNAME=$(echo $DATABASE_URL | awk -F[@//] '{print $3}' | cut -d: -f1)
    DB_PASSWORD=$(echo $DATABASE_URL | awk -F[@//] '{print $3}' | cut -d: -f2)

    # Mise à jour du fichier .env avec les valeurs extraites
    sed -i "s|DB_HOST=.*|DB_HOST=$DB_HOST|g" .env
    sed -i "s|DB_PORT=.*|DB_PORT=$DB_PORT|g" .env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|g" .env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|g" .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|g" .env
fi

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

# Vérification de la configuration
echo "🔍 Vérification de la configuration..."
php artisan config:clear
php artisan cache:clear

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
