ARG EMAIL
ARG NAME

FROM php:8.2-fpm

ARG EMAIL
ARG NAME

WORKDIR /var/www/app

RUN apt update  \
    && apt install -y zlib1g-dev g++ git libicu-dev zip libzip-dev zip \
    && docker-php-ext-install intl opcache pdo pdo_mysql \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony
RUN git config --global user.email "$EMAIL" \
    && git config --global user.name "$NAME"

RUN chown -R www-data:www-data /var/www/app