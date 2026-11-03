FROM php:8.3-cli-alpine
RUN apk add --no-cache postgresql-dev libzip-dev icu-dev libpng-dev libjpeg-turbo-dev libwebp-dev git unzip $PHPIZE_DEPS linux-headers \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install pdo_pgsql zip intl bcmath pcntl opcache gd \
 && pecl install redis && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS
# Dev: OPcache também no CLI (artisan serve/queue); revalida a cada 2 s para pegar edições.
COPY opcache-dev.ini /usr/local/etc/php/conf.d/opcache-dev.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app/backend
