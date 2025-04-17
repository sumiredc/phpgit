FROM php:8.4.6-cli-bullseye

WORKDIR /app

COPY php.ini "$PHP_INI_DIR/php.ini"

ENTRYPOINT [ "php", "src/app.php" ]
