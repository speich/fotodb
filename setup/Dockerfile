FROM php:7.3-apache


#apache config
RUN mkdir -p /etc/ssl/private/ \
    && a2enmod rewrite \
    && a2enmod ssl \
    && a2enmod http2
COPY fotodb.crt /etc/ssl/private/fotodb.crt
COPY fotodb.conf /etc/apache2/sites-available/fotodb.conf
RUN a2ensite fotodb
#COPY ./setup/php.ini /

# use your users $UID and $GID below
RUN groupadd apache-www-volume -g 1000
RUN useradd apache-www-volume -u 1000 -g 1000

# install PHP extension
RUN apt-get update \
    && apt-get install -y libicu-dev \
    && apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/  \
    && docker-php-ext-install gd exif intl

# install and setup xdebug
RUN pecl install xdebug-2.8.0
RUN docker-php-ext-enable xdebug
RUN echo "xdebug.remote_enable=1" >> /usr/local/etc/php/php.ini
RUN echo "xdebug.profiler_enable_trigger=1" >> /usr/local/etc/php/php.ini
RUN echo "xdebug.profiler_output_dir=/var/www/html" >> /usr/local/etc/php/php.ini


EXPOSE 80
EXPOSE 443