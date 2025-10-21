# Start from the same base image Render is using
FROM php:8.2-apache

# 1. Install system dependencies for PostgreSQL (libpq-dev)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && apt-get clean

# 2. Use the official PHP command to install and enable the pdo_pgsql extension
RUN docker-php-ext-install pdo_pgsql

# --- NEW LINES START HERE ---

# 3. Create the 'uploads' directory inside the web root
RUN mkdir -p /var/www/html/uploads

# 4. Change the owner of the 'uploads' directory to the web server user (www-data)
RUN chown www-data:www-data /var/www/html/uploads

# --- NEW LINES END HERE ---

# 5. Copy all your project files into the server's web directory
COPY . /var/www/html/