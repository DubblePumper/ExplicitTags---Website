FROM php:apache
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN apt-get update && apt-get upgrade -y
EXPOSE 9906 3306