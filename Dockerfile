FROM php:8.4-apache

# Extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git curl zip unzip libicu-dev libonig-dev libxml2-dev libzip-dev libssl-dev \
    && docker-php-ext-install intl mbstring xml zip pdo pdo_mysql mysqli opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Extension MongoDB (avec SSL)
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache : activer mod_rewrite
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Variables d'environnement Symfony (avant composer install pour que cache:clear s'exécute en prod)
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Copier le projet
WORKDIR /var/www/html
COPY . .

# Installer les dépendances (prod uniquement)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

# Permissions cache/logs
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var/ && chmod -R 775 var/

EXPOSE 80

# Vider le cache au démarrage
CMD ["apache2-foreground"]
