# Start from the official PHP 8.2 Apache image
FROM php:8.2-apache

# Step 1: Install system-level dependencies
# These are required by the PHP extensions we will install next.
# - GD needs libraries for handling fonts (freetype) and images (jpeg, png).
# - pdo_pgsql needs the PostgreSQL client library (libpq-dev).
# - zip and Composer need unzip and zip libraries.
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libpq-dev \
    libzip-dev \
    unzip \
    && apt-get clean

# Step 2: Configure and install the required PHP extensions
# The `docker-php-ext-install` command compiles and enables PHP modules.
# We need 'gd' for PhpSpreadsheet, 'pdo_pgsql' for the database, and 'zip'.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_pgsql zip

# Step 3: Install Composer (the PHP dependency manager) globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Step 4: Set the working directory for the rest of the build
WORKDIR /var/www/html

# Step 5: Install PHP dependencies using Composer
# We copy composer.json first to take advantage of Docker's caching.
# This step will fail if composer.json has errors.
COPY composer.json .
RUN composer install --no-dev --optimize-autoloader

# Step 6: Copy the rest of your application files into the web root
COPY . .

# Step 7 (FINAL STEP): Set permissions for the uploads directory AFTER all files are copied.
# This ensures that even if an 'uploads' folder exists in the repo, its permissions are corrected.
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads

