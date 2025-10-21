# Start from the official PHP 8.2 Apache image
FROM php:8.2-apache

# Install system dependencies required for extensions
# zip/unzip are for Composer and PhpSpreadsheet
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && apt-get clean

# Install required PHP extensions (pdo_pgsql for database, zip for excel files)
RUN docker-php-ext-install pdo_pgsql zip

# Install Composer (the PHP dependency manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create and set permissions for the uploads directory
RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads

# Set the working directory
WORKDIR /var/www/html

# Copy composer files and install dependencies
# This is done before copying the app for better Docker caching
COPY composer.json .
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of the application files
COPY . .