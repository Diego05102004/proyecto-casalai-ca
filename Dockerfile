# Usamos una imagen oficial de PHP con Apache incluido
FROM php:8.3-apache

# Habilitamos el módulo de reescritura de Apache por si usas rutas limpias
RUN a2enmod rewrite

# Copiamos todo el contenido de tu repositorio dentro de la carpeta pública del servidor
COPY . /var/www/html/

# Exponemos el puerto estándar web
EXPOSE 80
