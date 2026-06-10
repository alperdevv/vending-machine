# syntax=docker/dockerfile:1

# One reproducible image for development, testing and CI.
#
# The vending machine is a pure-domain library exercised through its test
# suite (and, later, a thin CLI), so this is a dev/test environment rather
# than a deployable server: PHP 8.4, the pcov coverage driver and Composer.
FROM php:8.4-cli-alpine

# pcov: a fast line-coverage driver used by PHPUnit and, later, by Infection.
# It is built through the PECL toolchain; the build dependencies are installed
# in a virtual package and dropped afterwards to keep the image small.
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .build-deps

# Composer from its official image, pinned to its major to keep builds stable.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
