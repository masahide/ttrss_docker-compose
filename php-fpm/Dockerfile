# vim: set ft=dockerfile:
FROM  php:fpm-alpine

MAINTAINER YAMASAKI Masahide <masahide.y@gmail.com>

RUN nproc=$(grep -c ^processor /proc/cpuinfo 2>/dev/null || 1) \
	&& apk --no-cache add \
		libmcrypt-dev \
	&& docker-php-ext-install -j${nproc} \
		mcrypt \
		mysqli

