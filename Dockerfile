# php:7.3-fpm
FROM php:7.3-apache


#apache config
RUN mkdir -p /etc/ssl/private/ \
    && a2enmod rewrite \
    && a2enmod ssl
    # && a2dismod mpm_prefork \
    # && a2dismod mpm_event \
    # && a2enmod mpm_worker \
    # && a2enmod http2
COPY ./setup/fotodb.crt /etc/ssl/private/fotodb.crt
COPY ./setup/fotodb.conf /etc/apache2/sites-available/fotodb.conf
RUN a2ensite fotodb
#COPY ./setup/php.ini /

# install PHP extension
RUN apt-get update \
    && apt-get install -y libicu-dev \
    && apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/  \
    && docker-php-ext-install gd exif

# install and setup xdebug
RUN pecl install xdebug-2.7.2
RUN docker-php-ext-enable xdebug
RUN echo "xdebug.remote_enable=1" >> /usr/local/etc/php/php.ini
RUN echo "xdebug.profiler_enable_trigger=1" >> /usr/local/etc/php/php.ini
RUN echo "xdebug.profiler_output_dir=/var/www/html" >> /usr/local/etc/php/php.ini


EXPOSE 80
EXPOSE 443