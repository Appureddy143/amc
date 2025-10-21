# Use the official PHP image with Apache pre-installed.
# Using a specific version like 8.2 is recommended for stability.
FROM php:8.2-apache

# --- DEPENDENCIES ---

# Install essential system libraries and PHP extensions.
# ADDED: gettext-base, which provides the 'envsubst' utility.
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    gettext-base \
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
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copy the rest of your application's source code.
COPY . .

# --- PERMISSIONS FIX ---

# Create the storage and cache directories if they don't exist.
RUN mkdir -p storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache


# --- RENDER CONFIGURATION (RUNTIME) ---

# 1. Create a TEMPLATE for our ports configuration during the build.
#    Note the escaped \$PORT, which writes the literal string "$PORT" to the file.
RUN echo "Listen \$PORT" > /etc/apache2/ports.conf.template

# 2. The CMD is executed at RUNTIME.
#    It first uses envsubst to substitute the PORT variable provided by Render
#    into our template, creating the final config. Then, it starts Apache.
CMD /bin/sh -c "envsubst < /etc/apache2/ports.conf.template > /etc/apache2/ports.conf && apache2-foreground"