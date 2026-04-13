FROM php:8.0-apache

# Enable Apache mod_rewrite for URL routing
RUN a2enmod rewrite

# Install required PHP extensions (MySQLi, PDO MySQL, PostgreSQL, etc.)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql pdo_pgsql

# Copy the application files to the Apache document root
COPY . /var/www/html/

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
