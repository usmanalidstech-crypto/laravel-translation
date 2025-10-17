FROM php:8.3-cli
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libicu-dev zlib1g-dev libssl-dev $PHPIZE_DEPS \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && docker-php-ext-install pdo_mysql zip intl \
 && apt-get purge -y --auto-remove $PHPIZE_DEPS \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
ARG LARAVEL_VERSION=^12.0
RUN composer create-project laravel/laravel:${LARAVEL_VERSION} . --no-interaction --prefer-dist

COPY overlay/ /var/www/html/
COPY overlay/scripts/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
