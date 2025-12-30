FROM php:8.3.6-apache

# Activer Apache rewrite (utile plus tard)
RUN a2enmod rewrite

# Installer extensions utiles
RUN docker-php-ext-install pdo_mysql opcache

# Configuration PHP
COPY php.ini /usr/local/etc/php/php.ini

# Dossier de travail
WORKDIR /var/www/html

# Droits (évite les problèmes)
RUN chown -R www-data:www-data /var/www/html

RUN mkdir -p /var/www/html/uploads \
 && chown -R www-data:www-data /var/www/html/uploads \
 && chmod -R 775 /var/www/html/uploads