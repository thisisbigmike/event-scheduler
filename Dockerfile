# Event Scheduling Management System - all-in-one demo image
# Apache + PHP 8.3 + MariaDB in one container, so it runs on any Docker host
# (Render, Railway, a VPS) with no other setup. Demo data is loaded on every start.
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends mariadb-server \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-install mysqli \
 && echo "mysqli.default_socket = /run/mysqld/mysqld.sock" > /usr/local/etc/php/conf.d/mysqli-socket.ini

# The application lives at the site root
COPY . /var/www/html/
RUN cp /var/www/html/config.sample.php /var/www/html/config.php \
 && cp /var/www/html/docker/apache-site.conf /etc/apache2/conf-available/event-scheduler.conf \
 && a2enconf event-scheduler \
 && chown -R www-data:www-data /var/www/html/uploads \
 && chmod +x /var/www/html/docker/start.sh

EXPOSE 80
CMD ["/var/www/html/docker/start.sh"]
