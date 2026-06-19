FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    curl unzip supervisor \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod rewrite

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p /opt/uploads-seed && \
    cp -r public/uploads/. /opt/uploads-seed/ 2>/dev/null || true

RUN mkdir -p public/uploads/products && chmod -R 777 public/uploads

EXPOSE 80 8080

CMD ["/start.sh"]
