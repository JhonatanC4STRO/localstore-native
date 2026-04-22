FROM php:8.2-apache

RUN apt-get update && apt-get install -y curl unzip && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN a2enmod rewrite headers

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p public/uploads/products && \
    chmod -R 777 public/uploads && \
    chown -R www-data:www-data /var/www/html

# Script creado aqui dentro para garantizar LF (sin CRLF de Windows)
RUN printf '#!/bin/sh\nPORT=${PORT:-80}\nsed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf\nsed -i "s/\\*:80>/\\*:$PORT>/" /etc/apache2/sites-available/000-default.conf\nexec apache2-foreground\n' > /start.sh && chmod +x /start.sh

EXPOSE 80

CMD ["/bin/sh", "/start.sh"]
