FROM php:7.1-fpm

WORKDIR "/application"

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -yqq --no-install-recommends install ca-certificates curl vim git \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN docker-php-ext-install mbstring
RUN pecl install redis-3.1.3

RUN pecl install xdebug-2.6.0 \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_port=9001" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_host=172.17.0.1" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN mkdir -p /application/data
RUN chmod a+rwx -R /application/data

RUN mkdir -p /application/user
RUN chmod a+rwx -R /application/user

RUN mkdir -p /application/users
RUN chmod a+rwx -R /application/users

RUN mkdir -p /application/log
RUN chmod a+rwx -R /application/log

RUN mkdir -p /application/tmp
RUN chmod a+rwx -R /application/tmp


RUN curl -Lsf 'https://storage.googleapis.com/golang/go1.8.3.linux-amd64.tar.gz' | tar -C '/usr/local' -xvzf -
ENV PATH /usr/local/go/bin:$PATH
RUN go get github.com/mailhog/mhsendmail
RUN cp /root/go/bin/mhsendmail /usr/bin/mhsendmail
RUN echo 'sendmail_path = /usr/bin/mhsendmail --smtp-addr mailhog:1025' >> /usr/local/etc/php/php.ini \
    && echo 'upload_max_filesize = 100M' >> /usr/local/etc/php/php.ini \
    && echo 'post_max_size = 100M' >> /usr/local/etc/php/php.ini
