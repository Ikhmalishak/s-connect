# docker/Dockerfile
FROM php:8.2-fpm-alpine

WORKDIR /var/www

# Install dependencies
RUN apk update && apk add \
    build-base \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create user and set permissions
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

USER www

# Copy application files
COPY --chown=www:www . /var/www

EXPOSE 9000
CMD ["php-fpm"]