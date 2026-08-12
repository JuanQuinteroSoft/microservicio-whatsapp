# Usar la imagen oficial de PHP 8.1
FROM php:8.1-apache

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Instalar extensiones de PHP necesarias
RUN apt-get update && apt-get install -y \
    curl \
    git \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP comunes
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    json \
    curl

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar los archivos del proyecto
COPY . .

# Habilitar el módulo rewrite de Apache
RUN a2enmod rewrite

# Configurar Apache para dirección a index.php
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteCond %{REQUEST_FILENAME} !-d\n\
    RewriteRule ^(.*)$ index.php [QSA,L]\n\
</Directory>' > /etc/apache2/sites-available/000-default.conf

# Configurar PHP.ini
RUN echo 'memory_limit = 256M\n\
max_execution_time = 300\n\
upload_max_filesize = 10M\n\
post_max_size = 10M\n\
default_charset = "UTF-8"' >> /usr/local/etc/php/conf.d/php-custom.ini

# Establecer permisos
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/storage

# Crear directorio de storage si no existe
RUN mkdir -p /var/www/html/storage/webhooks && \
    chown -R www-data:www-data /var/www/html/storage

# Exponer el puerto 80
EXPOSE 80

# Comando por defecto
CMD ["apache2-foreground"]
