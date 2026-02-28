# ─────────────────────────────────────────────────────────────
# Stage 1 — Base PHP
# ─────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS base

ARG APP_ENV=prod
ARG APP_VERSION=dev

ENV APP_ENV=${APP_ENV} \
    APP_VERSION=${APP_VERSION} \
    APP_DEBUG=0 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

# Extensions système
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-install \
    intl \
    pdo_mysql \
    zip \
    opcache \
    && docker-php-ext-configure intl \
    && rm -rf /var/cache/apk/*

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ─────────────────────────────────────────────────────────────
# Stage 2 — Development
# ─────────────────────────────────────────────────────────────
FROM base AS development

ENV APP_ENV=dev \
    APP_DEBUG=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=1

RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /tmp/pear

COPY docker/php/dev.ini /usr/local/etc/php/conf.d/app.ini

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

# ─────────────────────────────────────────────────────────────
# Stage 3 — Builder (install deps prod)
# ─────────────────────────────────────────────────────────────
FROM base AS builder

COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative \
    && php bin/console cache:warmup --env=prod

# ─────────────────────────────────────────────────────────────
# Stage 4 — Production (image finale légère)
# ─────────────────────────────────────────────────────────────
FROM base AS production

COPY docker/php/prod.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copier uniquement ce qui est nécessaire depuis le builder
COPY --from=builder /var/www/html/vendor ./vendor
COPY --from=builder /var/www/html/var/cache/prod ./var/cache/prod
COPY --from=builder /var/www/html .

# Permissions
RUN chown -R www-data:www-data var \
    && chmod -R 755 var

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

# Entrypoint
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER www-data
EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# Métadonnées OCI
LABEL org.opencontainers.image.source="https://github.com/${{ github.repository }}" \
    org.opencontainers.image.version="${APP_VERSION}" \
    org.opencontainers.image.licenses="MIT"