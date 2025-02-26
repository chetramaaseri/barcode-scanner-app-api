# Use Ubuntu as the base image
FROM ubuntu:latest

# Set the working directory
WORKDIR /var/www/html

# Install dependencies, add PPA for PHP, and install PHP 7.4 with required extensions
RUN apt-get update && apt-get install -y \
    apache2 \
    software-properties-common \
    curl \
    lsb-release \
    gnupg2 \
    git \
    unzip \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get update && apt-get install -y \
    php7.4 \
    php7.4-cli \
    php7.4-mysqli \
    php7.4-json \
    php7.4-common \
    php7.4-mbstring \
    php7.4-xml \
    php7.4-curl \
    php7.4-gd \
    php7.4-zip \
    libapache2-mod-php7.4 \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite (if needed for your application)
RUN a2enmod rewrite

# Configure Apache to allow .htaccess overrides (important for routing)
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/sites-available/000-default.conf

# Install Composer globally
# RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# # Copy composer.json and composer.lock
# COPY composer.json composer.lock /var/www/html/

# # Install PHP dependencies
# RUN composer install --no-dev --no-interaction --prefer-dist

# Copy the application files from the local folder to the container
COPY . /var/www/html/

# Set appropriate permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (HTTP)
EXPOSE 80

# Ensure Apache runs in the foreground (so container doesn't exit)
CMD ["apache2ctl", "-D", "FOREGROUND"]