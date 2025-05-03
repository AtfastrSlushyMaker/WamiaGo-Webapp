FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    g++ \
    libxslt1-dev \
    wkhtmltopdf \
    && docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    opcache \
    exif \
    gd \
    xsl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure Apache for Symfony
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN echo "AllowEncodedSlashes On" >> /etc/apache2/apache2.conf
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Copy project files
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data var
RUN chmod -R 777 var

# Set environment variables
ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1

# Generate cache for production
RUN php bin/console cache:clear --env=prod
RUN php bin/console cache:warmup --env=prod

# Expose port for Render (Render uses 10000 by default)
EXPOSE 8080

# Update Apache port to 8080 for Render
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf && \
    sed -i 's/*:80/*:8080/' /etc/apache2/sites-available/000-default.conf

# Start Apache
CMD ["apache2-foreground"]