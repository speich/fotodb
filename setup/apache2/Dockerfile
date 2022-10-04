FROM ubuntu/apache2:latest

RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install -y apt-utils

RUN apt-get install -y ssl-cert \
    && a2ensite default-ssl

RUN a2enmod rewrite \
    && a2enmod ssl  \
    && a2enmod http2  \
    && a2enmod headers \
    && a2enmod proxy_fcgi

#RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
EXPOSE 443