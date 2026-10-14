# Local WordPress image (§4.1). Hostinger runs PHP 8.2/8.3 on LiteSpeed; Apache
# with mod_rewrite is close enough and reads the same .htaccess.
FROM wordpress:php8.2-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends less mariadb-client \
 && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
 && chmod +x /usr/local/bin/wp

# Match the hPanel settings in §5.1.
RUN printf 'memory_limit=256M\nmax_execution_time=120\nupload_max_filesize=64M\npost_max_size=64M\n' \
    > /usr/local/etc/php/conf.d/techdella.ini

# Hostinger's LiteSpeed honours Header rules in .htaccess (e.g. X-Robots-Tag on lead-magnet PDFs).
RUN a2enmod headers

COPY td-entrypoint.sh /usr/local/bin/td-entrypoint.sh
RUN chmod +x /usr/local/bin/td-entrypoint.sh

ENTRYPOINT ["td-entrypoint.sh"]
CMD ["apache2-foreground"]
