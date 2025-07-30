FROM php:fpm-alpine

RUN addgroup -g 101 -S nginx
RUN adduser -u 101 -S -D -G nginx nginx

WORKDIR /var/www/html

RUN apk add libpq-dev fcgiwrap zlib-dev linux-headers bash ${PHPIZE_DEPS}

RUN pecl install apcu

RUN docker-php-ext-install pdo pdo_pgsql pgsql sockets

#RUN CFLAGS="$CFLAGS -D_GNU_SOURCE" docker-php-ext-install 

RUN docker-php-ext-enable apcu

COPY ./api/ /var/www/html/

COPY www.conf /usr/local/etc/php-fpm.d/www.conf

ENV SCRIPT_NAME=/daemon.php

ENV SCRIPT_FILENAME=/var/www/html/daemon.php

ENV REQUEST_METHOD=GET

CMD /usr/local/sbin/php-fpm -D -y /usr/local/etc/php-fpm.d/www.conf; sleep 1; while true; do bash -c "php health-check.php & disown; cgi-fcgi -bind -connect /sock/fpmsocket.sock"; done
