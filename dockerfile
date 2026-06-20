FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN sed -i 's/^#\(.*mod_rewrite\)/\1/' /etc/apache2/apache2.conf || true
RUN a2enmod rewrite

COPY . /var/www/html/

RUN echo '<Directory /var/www/html>' >> /etc/apache2/apache2.conf && \
    echo '    AllowOverride All' >> /etc/apache2/apache2.conf && \
    echo '</Directory>' >> /etc/apache2/apache2.conf

EXPOSE 80
