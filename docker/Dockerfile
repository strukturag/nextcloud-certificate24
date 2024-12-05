FROM node:23 AS builder

WORKDIR /workdir
COPY . .

ENV DEBIAN_FRONTEND=noninteractive \
	COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y \
			apt-transport-https \
			ca-certificates \
			lsb-release \
			rsync \
			&& \
		wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
		echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list && \
		\
		apt-get update && apt-get install -y \
			php8.2-cli \
			php8.2-mbstring \
			php8.2-xml \
			&& \
		curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN make npm-init && \
		make build-production && \
		make appstore && \
		mkdir build/output && \
		tar -C build/output -xf build/artifacts/certificate24.tar.gz


FROM nextcloud:30-apache

RUN apt-get update && apt-get install -y \
			supervisor \
			&& \
		rm -rf /var/lib/apt/lists/* && \
		mkdir /var/log/supervisord /var/run/supervisord

COPY docker/supervisord.conf /
COPY docker/prepare.sh /
COPY --from=builder /workdir/build/output/certificate24 /usr/src/nextcloud/custom_apps/certificate24/

ENV NEXTCLOUD_UPDATE=1

ENTRYPOINT [ "/prepare.sh" ]
CMD ["/usr/bin/supervisord", "-c", "/supervisord.conf"]
