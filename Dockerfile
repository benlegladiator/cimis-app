# Utiliser l'image PHP officielle avec Apache
FROM php:8.2-apache

# Installer les dépendances système et les extensions PHP requises
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd mysqli

# Activer le module Apache mod_rewrite (pour votre fichier .htaccess)
RUN a2enmod rewrite

# Copier tous les fichiers du projet dans le dossier web d'Apache
COPY . /var/www/html/

# Donner les bonnes permissions aux dossiers logs et uploads (pour que PHP puisse y écrire)
RUN chown -R www-data:www-data /var/www/html/logs /var/www/html/uploads \
    && chmod -R 775 /var/www/html/logs /var/www/html/uploads

# Exposer le port 80 (Render fera automatiquement le lien)
EXPOSE 80
