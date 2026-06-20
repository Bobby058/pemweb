#!/bin/bash
sed -i "s/Listen 80/Listen ${PORT:-8080}/" /etc/apache2/ports.conf
sed -i "s/*:80>/*:${PORT:-8080}>/" /etc/apache2/sites-enabled/000-default.conf
exec apache2ctl -D FOREGROUND
