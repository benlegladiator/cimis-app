# Utiliser l'image PHP officielle avec Apache
FROM php:8.2-apache

# Installer les dépendances système et les extensions PHP requises
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-install pdo_pgsql pgsql

# Activer le module Apache mod_rewrite (pour votre fichier .htaccess)
RUN a2enmod rewrite

# Copier tous les fichiers du projet dans le dossier web d'Apache
COPY . /var/www/html/

# Créer et donner les permissions aux dossiers requis (logs, uploads, photos)
RUN mkdir -p /var/www/html/img/candidats /var/www/html/img/qrcodes \
    && chown -R www-data:www-data /var/www/html/logs /var/www/html/uploads \
                                  /var/www/html/img/candidats /var/www/html/img/qrcodes \
    && chmod -R 775 /var/www/html/logs /var/www/html/uploads \
                    /var/www/html/img/candidats /var/www/html/img/qrcodes

# Augmenter les limites PHP pour les uploads de photos
RUN echo "upload_max_filesize = 5M\n\
post_max_size = 10M\n\
memory_limit = 256M\n\
max_execution_time = 120" > /usr/local/etc/php/conf.d/uploads.ini

# Exposer le port 80 (Render fera automatiquement le lien)
EXPOSE 80
