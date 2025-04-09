FROM php:8.4-cli

WORKDIR /app

ENTRYPOINT [ "php", "src/app.php" ]
