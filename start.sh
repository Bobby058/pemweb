#!/bin/bash
PORT=${PORT:-8080}

# Pass env vars ke php-fpm
printenv | grep -E "^(DB_|JWT_)" | sed 's/^/env[/' | sed 's/=/]="/' | sed 's/$/"/' >> /etc/php/8.2/fpm/pool.d/www.conf

sed -i "s/listen 80/listen $PORT/" /etc/nginx/sites-enabled/default
php-fpm8.2 -D
nginx -g 'daemon off;'
