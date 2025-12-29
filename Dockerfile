FROM serversideup/php:8.2-fpm-nginx

USER root

# System Dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libicu-dev \
    curl \
    gnupg \
    zip

# Node JS
RUN curl -fsSL https://deb.nodesource.com/setup_25.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql zip
RUN docker-php-ext-configure intl && docker-php-ext-install intl

# Copy application code
# COPY . /var/www/html
COPY --chown=www-data:www-data . /var/www/html

WORKDIR /var/www/html

RUN composer install 
RUN npm install
RUN npm run build