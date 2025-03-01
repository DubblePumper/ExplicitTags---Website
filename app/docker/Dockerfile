FROM php:apache

# Installeer benodigde PHP extensies en werk bij
RUN apt-get update && apt-get upgrade -y \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers \
    && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|' /etc/apache2/sites-available/000-default.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && service apache2 restart

EXPOSE 80
EXPOSE 81
EXPOSE 3306