#!/bin/bash
set -e

# Copier le fichier d'environnement Railway s'il existe
if [ -f ".env.railway" ]; then
    echo "Utilisation du fichier .env.railway..."
    cp .env.railway .env
else
    echo "Fichier .env.railway non trouvé, utilisation du fichier .env par défaut..."
fi

# Vérifier si APP_KEY existe et est valide
if [ -z "$APP_KEY" ] || [[ "$APP_KEY" == "base64:"* && ${#APP_KEY} -lt 50 ]]; then
    echo "Génération d'une nouvelle APP_KEY..."
    php -r "file_put_contents('.env', preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), file_get_contents('.env')));"
fi

# Créer le répertoire de la base de données s'il n'existe pas
if [ ! -d "/app/database" ]; then
    echo "Création du répertoire de la base de données..."
    mkdir -p /app/database
    touch /app/database/database.sqlite
    chmod -R 777 /app/database
fi

# Créer le lien symbolique pour le stockage
echo "Création du lien symbolique pour le stockage..."
php artisan storage:link --force

# Exécuter les migrations
echo "Exécution des migrations..."
php artisan migrate --force

# Optimiser l'application
echo "Optimisation de l'application..."
php artisan optimize
php artisan view:cache
php artisan config:cache
php artisan route:cache

echo "Démarrage de l'application..."
exec "$@"
