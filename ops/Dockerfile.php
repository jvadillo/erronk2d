FROM php:8.4-fpm-alpine
RUN apk add --no-cache libpq libzip icu-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev libzip-dev icu-dev sqlite-dev \
    && docker-php-ext-install -j1 pdo_pgsql pdo_sqlite intl zip pcntl opcache \
    && apk del .build-deps
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
RUN mkdir -p /app && chown 1000:1000 /app
USER 1000:1000
CMD ["php-fpm", "-F"]
