FROM php:fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y libpq-dev fcgiwrap

RUN docker-php-ext-install pdo pdo_pgsql pgsql


ENV SCRIPT_NAME=/daemon.php

ENV SCRIPT_FILENAME=/var/www/html/daemon.php

ENV REQUEST_METHOD=GET

CMD /usr/local/sbin/php-fpm -D; sleep 1; /usr/bin/bash -c "while true; do cgi-fcgi -bind -connect localhost:9000 2>&1; done"
