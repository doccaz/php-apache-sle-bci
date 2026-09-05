ARG PHP_TAG=8

FROM registry.suse.com/bci/php-apache:${PHP_TAG}
LABEL maintainer="erico.mendonca@suse.com"

# Extra extensions on top of what bci/php-apache ships by default (curl,
# mbstring, openssl, session, zip, ...): PDO+SQLite for the sample app's
# visit counter, opcache for a production-sane default, gd/intl as the
# two extensions the upstream image's own docs use as their install example.
RUN zypper -n install --no-recommends \
        php8-pdo php8-sqlite php8-opcache php8-gd php8-intl \
    && zypper -n clean --all

# Official Composer installer, integrity-checked against its published
# signature before executing it.
RUN curl -fsSL https://raw.githubusercontent.com/composer/getcomposer.org/main/web/installer -o /tmp/composer-setup.php \
    && curl -fsSL https://composer.github.io/installer.sig -o /tmp/composer-setup.sig \
    && php -r "if (hash_file('sha384', '/tmp/composer-setup.php') !== trim(file_get_contents('/tmp/composer-setup.sig'))) { fwrite(STDERR, \"composer installer signature mismatch\n\"); exit(1); }" \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm -f /tmp/composer-setup.php /tmp/composer-setup.sig

COPY app/ .
RUN mkdir -p data && chown wwwrun:www data

EXPOSE 80
