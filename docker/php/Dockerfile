FROM php:8.2-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*


RUN groupadd -g ${GID} appgroup \
    && useradd -u ${UID} -g appgroup -m appuser

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

USER appuser

CMD ["php-fpm"]