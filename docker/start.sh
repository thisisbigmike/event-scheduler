#!/bin/sh
# Container start-up: listen on the host's port, start MariaDB, load demo data, start Apache.
set -e

# Hosting services such as Render tell the app which port to use in $PORT
PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Start the database (only reachable from inside this container)
mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld
mysqld_safe --bind-address=127.0.0.1 >/dev/null 2>&1 &
i=0
until mariadb -e "SELECT 1" >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -gt 60 ]; then echo "MariaDB did not start"; exit 1; fi
    sleep 1
done

# Fresh demo data on every start (event dates are relative to today)
mariadb < /var/www/html/database/eventdb.sql
echo "Event Scheduler ready on port ${PORT}"

exec apache2-foreground
