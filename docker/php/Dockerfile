# PHP 8.3 + Apache + extensión OCI8 (Oracle Instant Client)
FROM php:8.3-apache-bookworm

ENV ORACLE_HOME=/opt/oracle/instantclient \
    LD_LIBRARY_PATH=/opt/oracle/instantclient \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends libaio1 unzip curl ca-certificates; \
    mkdir -p /opt/oracle; \
    cd /opt/oracle; \
    curl -fsSLo basic.zip https://download.oracle.com/otn_software/linux/instantclient/instantclient-basiclite-linuxx64.zip; \
    curl -fsSLo sdk.zip   https://download.oracle.com/otn_software/linux/instantclient/instantclient-sdk-linuxx64.zip; \
    unzip -q basic.zip; \
    unzip -q -o sdk.zip; \
    rm -f basic.zip sdk.zip; \
    mv instantclient_* instantclient; \
    echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle-instantclient.conf; \
    ldconfig; \
    docker-php-ext-configure oci8 --with-oci8=instantclient,/opt/oracle/instantclient; \
    docker-php-ext-install -j"$(nproc)" oci8; \
    a2enmod rewrite headers; \
    sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf; \
    apt-get purge -y unzip; \
    apt-get autoremove -y; \
    rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-umg.ini
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
