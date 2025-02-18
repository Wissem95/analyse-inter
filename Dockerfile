FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Installation de Node.js
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm

# Configuration d'Apache
RUN a2enmod rewrite headers
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configuration du VirtualHost
COPY docker/8.2/000-default.conf /etc/apache2/sites-available/000-default.conf

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers du projet
COPY . .

# Préparation de l'environnement
COPY .env.example .env

# Installation des dépendances PHP sans exécuter les scripts
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Génération de la clé d'application
RUN php artisan key:generate

# Installation des dépendances Node.js et build
RUN npm ci && npm run build

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copie du script de démarrage
COPY docker/8.2/start-railway.sh /usr/local/bin/start-railway
RUN chmod +x /usr/local/bin/start-railway

EXPOSE 80

CMD ["/usr/local/bin/start-railway"]
