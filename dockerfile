FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
          /etc/apache2/mods-enabled/mpm_*.conf && \
    ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/ && \
    ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/ && \
    a2enmod rewrite

COPY . /var/www/app/

RUN cat > /etc/apache2/sites-enabled/000-default.conf << 'EOF'
<VirtualHost *:80>
    DocumentRoot /var/www/app/public

    Alias /src /var/www/app/src

    <Directory /var/www/app>
        AllowOverride All
        Require all granted
        Options FollowSymLinks
    </Directory>

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ /src/server.php [L]
</VirtualHost>
EOF

RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
