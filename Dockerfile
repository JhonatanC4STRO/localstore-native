FROM php:8.2-cli

RUN apt-get update && apt-get install -y curl unzip && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader

# Guardar copia de las imagenes originales fuera del volumen
RUN mkdir -p /opt/uploads-seed && \
    cp -r public/uploads/. /opt/uploads-seed/ 2>/dev/null || true

# Crear la carpeta y dar permisos (por si el volumen no existe)
RUN mkdir -p public/uploads/products && chmod -R 777 public/uploads

# Script de inicio: copia las imagenes al volumen si esta vacio
RUN printf '#!/bin/sh\nmkdir -p /var/www/html/public/uploads/products\nif [ ! -f /var/www/html/public/uploads/.seeded ]; then\n  cp -rn /opt/uploads-seed/. /var/www/html/public/uploads/ 2>/dev/null || true\n  touch /var/www/html/public/uploads/.seeded\nfi\nchmod -R 777 /var/www/html/public/uploads\nexec php -S 0.0.0.0:${PORT:-80} -t /var/www/html\n' > /start.sh && chmod +x /start.sh

CMD ["/bin/sh", "/start.sh"]
