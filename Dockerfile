FROM php:8.0-apache

# Enable Apache mod_rewrite for URL routing
RUN a2enmod rewrite

# Install required PHP extensions (PostgreSQL, PDO, etc.)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql mbstring zip xml

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the application files to the Apache document root
COPY . /var/www/html/

# Install PHP dependencies via Composer
RUN composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs

# Update the default apache site with the config we need
RUN echo "<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php\n\
    </Directory>\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port (Render automatically uses PORT environment variable, Apache defaults to 80)
EXPOSE 80
