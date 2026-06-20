RUN cat > /etc/nginx/sites-enabled/default << 'EOF'
server {
    listen 80;
    root /var/www/app/public;
    index index.html;

    location ~* \.(html|css|js|png|jpg|ico)$ {
        try_files $uri =404;
    }

    location / {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/app/src/server.php;
        fastcgi_param REQUEST_URI $request_uri;
        fastcgi_param REQUEST_METHOD $request_method;
        include fastcgi_params;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF
