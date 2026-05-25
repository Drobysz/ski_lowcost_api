FROM php:8.3-cli-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        unzip \
    && docker-php-ext-install \
        bcmath \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

EXPOSE 8000

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]
