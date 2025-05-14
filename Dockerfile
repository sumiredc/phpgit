FROM php:8.4.7-cli-alpine

WORKDIR /app

COPY php.ini "$PHP_INI_DIR/php.ini"

ENTRYPOINT [ "php", "src/app.php" ]
