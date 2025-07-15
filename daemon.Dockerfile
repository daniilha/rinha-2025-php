FROM php:latest

WORKDIR /srv

RUN apt-get update && apt-get install -y libpq-dev

RUN docker-php-ext-install pdo pdo_pgsql pgsql

RUN kill -USR2 1

CMD [ "php", "./daemon.php" ]
