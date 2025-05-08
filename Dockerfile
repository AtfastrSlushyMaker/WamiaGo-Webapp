FROM php:8.2-apache

# Set environment variables early
ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1
# Increase PHP memory limit
ENV PHP_MEMORY_LIMIT=1024M
# Set Composer environment variables to avoid prompts
ENV COMPOSER_NO_INTERACTION=1

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
    mysqli \
    soap \
    bcmath \
    && docker-php-ext-configure gd --with-freetype --with-jpeg

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure Apache for Symfony
RUN a2enmod rewrite
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN echo "AllowEncodedSlashes On" >> /etc/apache2/apache2.conf
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Set PHP configuration
RUN echo "memory_limit = ${PHP_MEMORY_LIMIT}" > $PHP_INI_DIR/conf.d/memory-limit.ini
RUN echo "upload_max_filesize = 20M" > $PHP_INI_DIR/conf.d/upload-limit.ini
RUN echo "post_max_size = 20M" >> $PHP_INI_DIR/conf.d/upload-limit.ini

# Copy composer files and install dependencies
COPY composer.json composer.lock ./

# Allow required plugins
RUN composer config --no-plugins allow-plugins.php-http/discovery true
RUN composer config --no-plugins allow-plugins.endroid/installer true
RUN composer config --no-plugins allow-plugins.symfony/flex true
RUN composer config --no-plugins allow-plugins.symfony/runtime true

# Install dependencies for production only (excludes dev packages like DebugBundle)
RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-scripts

# Now copy the rest of the files
COPY . .

# Create a .env.local file to ensure we're in production mode
RUN echo "APP_ENV=prod" > .env.local
RUN echo "APP_DEBUG=0" >> .env.local

# Run scripts after all files are copied
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Set permissions
RUN mkdir -p var/cache var/log
RUN chown -R www-data:www-data var
RUN chmod -R 777 var

# Generate cache for production with explicit environment
RUN APP_ENV=prod php bin/console cache:clear --no-warmup --no-debug --env=prod || echo "Could not clear cache, continuing anyway"
RUN APP_ENV=prod php bin/console cache:warmup --env=prod || echo "Could not warm up cache, continuing anyway"

# Expose port for Render (Render expects port 8080)
EXPOSE 8080

# Update Apache port to 8080 for Render
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf && \
    sed -i 's/*:80/*:8080/' /etc/apache2/sites-available/000-default.conf

# Start Apache
CMD ["apache2-foreground"]