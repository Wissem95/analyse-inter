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
    postgresql-client \
    dos2unix \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Installation de Node.js
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@10.2.4

# Configuration d'Apache
RUN a2enmod rewrite headers
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configuration du VirtualHost
COPY docker/8.2/000-default.conf /etc/apache2/sites-available/000-default.conf

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copie de tous les fichiers du projet
COPY . .

# Copie du fichier d'environnement
COPY .env.render .env

# Installation des dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Build des assets
ENV NODE_OPTIONS="--max-old-space-size=8192"
RUN npm ci && \
    npm run build && \
    mkdir -p public/build && \
    cp -r build/* public/build/

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copie et configuration du script de démarrage
COPY docker/8.2/start-render.sh /usr/local/bin/start-render
RUN chmod +x /usr/local/bin/start-render \
    && dos2unix /usr/local/bin/start-render

EXPOSE 80

CMD ["/usr/local/bin/start-render"]
