FROM php:8.2-cli

# Instala dependências básicas e extensões necessárias
RUN apt-get update \
    && apt-get install -y git unzip libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql bcmath \
    && rm -rf /var/lib/apt/lists/*

# Instala Composer globalmente
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
