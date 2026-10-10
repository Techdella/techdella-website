#!/bin/sh
# Apache listens on the same port inside and outside the container
# (e.g. 8081), so home_url() works for requests made from inside the
# container too (wp td redirects:verify, loopback requests, cron).
set -e
PORT="${APACHE_PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
exec docker-entrypoint.sh "$@"
