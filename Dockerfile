FROM php:8.2-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Installer les extensions PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

# Installer les dépendances PHP
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-plugins --no-scripts

# Installer les dépendances Node.js
RUN npm ci --ignore-scripts

# Copier le reste des fichiers
COPY . .

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p /app/database /app/storage && \
    chmod -R 777 /app/storage /app/database

# Exposer le port
EXPOSE 8000

# Démarrer l'application
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
