FROM php:8.2-cli
RUN apt-get update && apt-get install -y libzip-dev unzip libfreetype6-dev libjpeg62-turbo-dev libpng-dev libonig-dev \
    && docker-php-ext-install pdo_sqlite curl mbstring gd
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN chmod -R 775 storage database
EXPOSE 8080
CMD ["php","-S","0.0.0.0:8080","-t","public"]
