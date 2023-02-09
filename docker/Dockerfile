FROM node:16 AS builder

WORKDIR /workdir
COPY . .

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
			apt-transport-https \
			ca-certificates \
			lsb-release \
			&& \
		wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
		echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list && \
		\
		apt-get update && apt-get install -y \
			php7.4-cli \
			&& \
		curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN npm ci && \
		npm run build && \
		composer install && \
		rm -rf composer.* && \
		rm -rf package* && \
		rm -rf *.js && \
		rm -rf js/*.map && \
		\
		rm -rf docker/ && \
		rm -rf docs/ && \
		rm -rf node_modules/ && \
		rm -rf src/


FROM nextcloud:25-apache

RUN apt-get update && apt-get install -y \
			supervisor \
			&& \
		rm -rf /var/lib/apt/lists/* && \
		mkdir /var/log/supervisord /var/run/supervisord

COPY docker/supervisord.conf /
COPY docker/prepare.sh /
COPY --from=builder /workdir /usr/src/nextcloud/custom_apps/esig/

ENV NEXTCLOUD_UPDATE=1

ENTRYPOINT [ "/prepare.sh" ]
CMD ["/usr/bin/supervisord", "-c", "/supervisord.conf"]
