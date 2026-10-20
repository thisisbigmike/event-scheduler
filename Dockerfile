# Event Scheduling Management System - all-in-one demo image
# Apache + PHP 8.3 + MariaDB in one container, so it runs on any Docker host
# (Render, Railway, a VPS) with no other setup. Demo data is loaded on every start.
FROM php:8.3-apache-trixie

RUN apt-get update \
 && apt-get install -y --no-install-recommends mariadb-server \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-install mysqli \
 && echo "mysqli.default_socket = /run/mysqld/mysqld.sock" > /usr/local/etc/php/conf.d/mysqli-socket.ini \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'session.cookie_httponly = 1\nsession.use_strict_mode = 1\nsession.cookie_samesite = Lax\nexpose_php = Off\n' > "$PHP_INI_DIR/conf.d/zz-hardening.ini" \
 && sed -i -e 's/^ServerTokens OS/ServerTokens Prod/' -e 's/^ServerSignature On/ServerSignature Off/' /etc/apache2/conf-available/security.conf

# The application lives at the site root
COPY . /var/www/html/
RUN cp /var/www/html/config.sample.php /var/www/html/config.php \
 && cp /var/www/html/docker/apache-site.conf /etc/apache2/conf-available/event-scheduler.conf \
 && a2enconf event-scheduler \
 && chown root:root /var/www/html && chmod 755 /var/www/html \
 && chown -R www-data:www-data /var/www/html/uploads \
 && chmod +x /var/www/html/docker/start.sh

EXPOSE 80
CMD ["/var/www/html/docker/start.sh"]
