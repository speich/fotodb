FROM php:7.2-apache
EXPOSE 80
EXPOSE 443

# install GD
RUN apt-get update \
    && apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd

# Install Intl
RUN apt-get update \
    && apt-get install -y libicu-dev \
    && docker-php-ext-install intl

# install and setup xdebug
RUN pecl install xdebug-2.6.0
RUN docker-php-ext-enable xdebug
RUN echo "xdebug.remote_enable=1" >> /usr/local/etc/php/php.ini
RUN echo "xdebug.profiler_enable_trigger=1" >> /usr/local/etc/php/php.ini
RUN echo "xdebug.profiler_output_dir=/var/www/xdebug/snapshots" >> /usr/local/etc/php/php.ini