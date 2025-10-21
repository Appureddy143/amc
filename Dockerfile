# Use the official PHP image with Apache pre-installed.
# Using a specific version like 8.2 is recommended for stability.
FROM php:8.2-apache

# --- DEPENDENCIES ---

# Install essential system libraries and PHP extensions.
# Added: libzip-dev (for zip), libicu-dev (for intl)
# Added extensions: zip, intl, bcmath
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mysqli zip intl bcmath

# Enable Apache's mod_rewrite for friendly URLs (e.g., for Laravel, Symfony, etc.)
RUN a2enmod rewrite

# Install Composer (PHP package manager) globally.
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer


# --- APPLICATION CODE ---

# Set the working directory to Apache's default web root.
WORKDIR /var/www/html

# Copy composer dependency definitions.
COPY composer.json composer.lock ./

# Install Composer dependencies.
# Using --no-dev and --optimize-autoloader is best practice for production.
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copy the rest of your application's source code.
COPY . .

# Set the correct permissions for storage and cache directories.
# This is crucial for frameworks like Laravel.
# The 'www-data' user is the one Apache runs as.
RUN chown -R www-data:www-data storage bootstrap/cache


# --- RENDER CONFIGURATION ---

# Render provides the PORT environment variable.
# We need to configure Apache to listen on this port.
# This command overwrites the default port configuration.
RUN echo "Listen \${PORT:-10000}" > /etc/apache2/ports.conf