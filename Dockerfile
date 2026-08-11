FROM php:8.2-apache

# Installer les dépendances système et extensions PHP nécessaires (pdo_mysql, gd)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*

# Activer mod_rewrite Apache
RUN a2enmod rewrite

# Copier les fichiers du projet
COPY . /var/www/html/

# Ajuster la configuration Apache pour écouter sur le PORT dynamique de Render ($PORT)
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80 8080
