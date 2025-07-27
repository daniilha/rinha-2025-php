FROM php:fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y libpq-dev fcgiwrap zlib1g-dev 

RUN pecl install apcu

RUN docker-php-ext-install pdo pdo_pgsql pgsql 

RUN docker-php-ext-enable apcu

ENV SCRIPT_NAME=/daemon.php

ENV SCRIPT_FILENAME=/var/www/html/daemon.php

ENV REQUEST_METHOD=GET

CMD /usr/local/sbin/php-fpm -D; sleep 1; while true; do bash -c "php health-check.php & disown; cgi-fcgi -bind -connect localhost:9000;"; done
