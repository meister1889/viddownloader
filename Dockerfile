# Use official PHP image with Apache
FROM php:8.2-apache

# Enable Apache modules required by .htaccess
RUN a2enmod rewrite headers expires deflate mime

# Install required system packages and PHP extensions
# The application uses cURL which is typically included, but we ensure basic utilities are present
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libonig-dev \
    && docker-php-ext-install curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html/

# Copy the application code to the container
COPY . /var/www/html/

# Create tmp directory if it doesn't exist and set permissions
RUN mkdir -p /var/www/html/tmp && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Configure Apache DocumentRoot (if needed, default is /var/www/html)
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Ensure Apache allows overrides for .htaccess
RUN echo "<Directory /var/www/html/>\n\tAllowOverride All\n</Directory>" >> /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
