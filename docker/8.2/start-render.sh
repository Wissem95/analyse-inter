#!/bin/bash
set -e

echo "🚀 Démarrage du processus de déploiement..."

# Configuration de l'URL de la base de données Neon
export DATABASE_URL="postgresql://neondb_owner:npg_wB9xK2dDjSWm@ep-tiny-cell-a8nfck52-pooler.eastus2.azure.neon.tech/neondb?sslmode=require"

# Vérification du contenu du fichier .env
echo "📄 Contenu du fichier .env:"
cat .env

# Création des répertoires nécessaires
echo "📁 Création des répertoires de stockage..."
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Configuration des permissions
echo "👮 Configuration des permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage/framework
chmod -R 775 /var/www/html/storage/logs

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

# Création manuelle des tables
echo "🔄 Création des tables..."
PGPASSWORD=npg_wB9xK2dDjSWm psql "postgresql://neondb_owner@ep-tiny-cell-a8nfck52-pooler.eastus2.azure.neon.tech/neondb?sslmode=require" << 'EOSQL'
-- Table migrations
CREATE TABLE IF NOT EXISTS migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

-- Table users
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Table password_reset_tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);

-- Table import_history
CREATE TABLE IF NOT EXISTS import_history (
    id BIGSERIAL PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    records_count INTEGER NOT NULL,
    import_date TIMESTAMP NOT NULL,
    status VARCHAR(255) NOT NULL,
    errors JSONB NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);

-- Table interventions
CREATE TABLE IF NOT EXISTS interventions (
    id BIGSERIAL PRIMARY KEY,
    date_intervention DATE NOT NULL,
    technicien VARCHAR(255) NOT NULL,
    type_intervention VARCHAR(255) NOT NULL,
    type_operation VARCHAR(255) NULL,
    type_habitation VARCHAR(255) NULL,
    prix DECIMAL(10,2) NOT NULL,
    revenus_percus DECIMAL(10,2) DEFAULT 0,
    import_id BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (import_id) REFERENCES import_history(id) ON DELETE CASCADE
);

-- Marquer les migrations comme terminées
INSERT INTO migrations (migration, batch) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2024_02_15_000000_create_interventions_table', 1),
('2024_03_15_000000_create_import_history_table', 1),
('2025_02_17_231502_add_import_id_to_interventions', 1);
EOSQL

# Reconstruction des assets
echo "🎨 Reconstruction des assets..."
npm run build

# Création du lien symbolique pour le stockage
echo "🔗 Création du lien symbolique pour le stockage..."
php artisan storage:link || true

# Optimisation pour la production
echo "⚡ Optimisation de l'application..."
php artisan optimize --force
php artisan view:cache
php artisan config:cache
php artisan route:cache

# Vérification des permissions des assets
echo "👮 Vérification des permissions des assets..."
chown -R www-data:www-data /var/www/html/public/build
chmod -R 775 /var/www/html/public/build

echo "✅ Déploiement terminé!"
echo "🌐 Démarrage d'Apache..."

# Démarrage d'Apache en premier plan
apache2-foreground
