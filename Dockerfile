FROM php:8.2-apache

# Activer le module rewrite d'Apache
RUN a2enmod rewrite headers

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm

# Installer les extensions PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip xml

# Installer les extensions PHP supplémentaires via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurer Apache
ENV APACHE_DOCUMENT_ROOT=/app/public

# Copier la configuration Apache
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers

# Définir le répertoire de travail
WORKDIR /app

# Copier tous les fichiers du projet
COPY . .

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p /app/database /app/storage && \
    chmod -R 777 /app/storage /app/database && \
    chmod -R 777 /app/bootstrap/cache && \
    chown -R www-data:www-data /app

# Créer le fichier .env à partir de .env.example
RUN cp .env.example .env && \
    php -r "file_put_contents('.env', str_replace('APP_KEY=', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), file_get_contents('.env')));"

# Installer les dépendances PHP
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Installer les dépendances Node.js
RUN npm ci --ignore-scripts

# Construire les assets
RUN npm run build

# Script de démarrage
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Démarrer l'application
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
