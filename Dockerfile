# Start from the same base image Render is using
FROM php:8.2-apache

# 1. Install the system dependencies for PostgreSQL (libpq-dev)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && apt-get clean

# 2. Use the official PHP command to install and enable the pdo_pgsql extension
RUN docker-php-ext-install pdo_pgsql

# 3. Copy all your project files into the server's web directory
# (Your phpinfo shows this is /var/www/html)
COPY . /var/www/html/
