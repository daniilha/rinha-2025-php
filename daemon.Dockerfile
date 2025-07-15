FROM php:fpm

WORKDIR /srv

RUN apt-get update && apt-get install -y libpq-dev

RUN docker-php-ext-install pdo pdo_pgsql pgsql

ENV PHP_EXTRA_CONFIGURE_ARGS --enable-maintainer-zts

# RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN kill -USR2 1

CMD [ "php", "./daemon.php" ]
