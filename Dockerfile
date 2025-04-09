FROM php:8.4.5-cli-bullseye

WORKDIR /app

ENTRYPOINT [ "php", "src/app.php" ]
