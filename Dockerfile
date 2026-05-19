FROM dunglas/frankenphp

RUN install-php-extensions pdo pdo_mysql

COPY . /app/public

ENV SERVER_NAME=:8080

CMD ["frankenphp", "run"]