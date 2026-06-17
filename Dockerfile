# 1. Usamos una imagen oficial de PHP con Apache
FROM php:8.3-apache

# 2. Instalamos las herramientas del sistema necesarias para Composer y extensiones
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip mysqli pdo_mysql

# 3. Instalamos Composer de manera oficial dentro del contenedor
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Habilitamos el módulo de reescritura de Apache
RUN a2enmod rewrite

# 5. Copiamos los archivos de tu proyecto al servidor de Render
COPY . /var/www/html/

# 6. Definimos el directorio de trabajo e instalamos dependencias de Composer automáticamente
WORKDIR /var/www/html
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 7. Exponemos el puerto estándar web
EXPOSE 80
