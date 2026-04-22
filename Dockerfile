FROM php:8.2-apache

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    supervisor \
    curl \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Módulos Apache
RUN a2enmod rewrite headers

# Configuración Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Supervisord
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf

WORKDIR /var/www/html

COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Permisos de uploads
RUN mkdir -p public/uploads/products && \
    chmod -R 777 public/uploads && \
    chown -R www-data:www-data /var/www/html

# Script de inicio
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
