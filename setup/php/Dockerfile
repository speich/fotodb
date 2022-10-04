FROM php:8.1-fpm

RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install -y apt-utils

RUN apt-get install -y libicu-dev \
    && docker-php-ext-install intl

RUN apt-get install -y libenchant-2-2 libenchant-2-dev aspell-de hunspell-de-ch \
    && docker-php-ext-install enchant

RUN docker-php-ext-install exif

RUN apt-get install -y libmagickwand-dev \
  && pecl install imagick \
  && docker-php-ext-enable imagick

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \