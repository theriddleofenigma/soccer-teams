FROM php:8.1.18-fpm

# Install nginx and other dependencies
RUN apt-get update && apt-get install -y nginx libmcrypt-dev mcrypt zip unzip \
    zlib1g zlib1g-dev libbz2-dev libzip-dev libonig-dev  libssl-dev  \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev

# Install php pdo-mysql and gd extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp
RUN docker-php-ext-install pdo_mysql gd

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Cleanup
RUN apt-get autoremove -y

# Copy php.ini, cong file etc file
COPY docker/conf.d/default.conf /etc/nginx/conf.d/default.conf
COPY docker/fpm/www.conf /usr/local/etc/php-fpm.d/
COPY docker/fpm/php.ini /usr/local/etc/php

# Copy existing application directory contents
COPY . /var/www/html

# Working directory
WORKDIR /var/www/html

RUN echo "RUNNING COMPOSER INSTALL" && COMPOSER_MEMORY_LIMIT=-1 composer install --no-plugins --no-interaction --optimize-autoloader --no-dev \
    && chown -R www-data:www-data /var/www/html/storage

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
