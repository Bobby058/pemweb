FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.2 \
    php8.2-mysql \
    libapache2-mod-php8.2 \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite php8.2

COPY . /var/www/html/

RUN echo '<Directory /var/www/html>' >> /etc/apache2/apache2.conf && \
    echo '    AllowOverride All' >> /etc/apache2/apache2.conf && \
    echo '</Directory>' >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]
