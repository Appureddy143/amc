# Start from the official PHP 8.2 Apache image
FROM php:8.2-apache

# Install system dependencies required for extensions
# - libfreetype-dev, libjpeg-dev, libpng-dev are for the GD extension
# - libpq-dev is for PostgreSQL
# - libzip-dev and unzip are for Composer and PhpSpreadsheet
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libpq-dev \
    libzip-dev \
    unzip \
    && apt-get clean

# Configure and install the GD extension, then other required extensions
# Using -j$(nproc) makes the installation faster
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_pgsql zip

# Install Composer (the PHP dependency manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create and set permissions for the uploads directory
RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads

# Set the working directory for the build
WORKDIR /var/www/html

# Copy your composer.json file and install the dependencies
# This is done before copying the rest of the app for better Docker caching
COPY composer.json .
RUN composer install --no-dev --optimize-autoloader

# Now, copy the rest of your application files
COPY . .

