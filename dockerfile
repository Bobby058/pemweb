FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.2 \
    php8.2-mysql \
    libapache2-mod-php8.2 \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite php8.2

COPY . /var/www/app/

RUN sed -i 's|/var/www/html|/var/www/app/public|g' /etc/apache2/sites-available/000-default.conf && \
    sed -i 's|/var/www/html|/var/www/app/public|g' /etc/apache2/sites-enabled/000-default.conf && \
    echo 'ServerName localhost' >> /etc/apache2/apache2.conf

EXPOSE 8080

CMD ["bash", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-8080}/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:\\'${PORT:-8080}'>/ \" /etc/apache2/sites-enabled/000-default.conf && apache2ctl -D FOREGROUND"]
