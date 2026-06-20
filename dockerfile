FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.2 \
    php8.2-mysql \
    php8.2-cli \
    libapache2-mod-php8.2 \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite php8.2

COPY . /var/www/app/

RUN chown -R www-data:www-data /var/www/app && \
    chmod -R 755 /var/www/app

RUN cat > /etc/apache2/sites-enabled/000-default.conf << 'EOF'
<VirtualHost *:80>
    DocumentRoot /var/www/app/public
    DirectoryIndex index.html index.php

    Alias /src /var/www/app/src

    <Directory /var/www/app/public>
        AllowOverride None
        Require all granted
        Options FollowSymLinks

        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ /var/www/app/src/server.php [L]
    </Directory>

    <Directory /var/www/app/src>
        Require all granted
    </Directory>

    ErrorLog /dev/stderr
    CustomLog /dev/stdout combined
</VirtualHost>
EOF

RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
