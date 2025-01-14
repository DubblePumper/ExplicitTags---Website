FROM php:apache
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN apt-get update && apt-get upgrade -y

# Enable Apache mod_rewrite and configure AllowOverride
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

EXPOSE 9906 3306