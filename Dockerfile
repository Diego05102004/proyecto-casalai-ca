# 1. Usamos tu imagen de PHP
FROM php:8.3-apache

# 2. Instalamos las herramientas del sistema necesarias para Composer y extensiones
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip mysqli pdo_mysql

# 3. Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Habilitamos los módulos de Apache necesarios (reescritura y headers)
RUN a2enmod rewrite headers

# --- NUEVA CORRECCIÓN PARA EL ERROR 426 ---
# Configuramos Apache para que acepte las peticiones detrás del proxy SSL de Render
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# 5. Copiamos los archivos de tu proyecto
COPY . /var/www/html/

# 6. Definimos el directorio de trabajo e instalamos dependencias de Composer
WORKDIR /var/www/html
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 7. Exponemos el puerto dinámico asignado por Render (en lugar de forzar el 80 fijo)
EXPOSE 80
