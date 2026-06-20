#!/bin/bash
PORT=${PORT:-8080}
sed -i "s/listen 80/listen $PORT/" /etc/nginx/sites-enabled/default
php-fpm8.2 -D
nginx -g 'daemon off;'
