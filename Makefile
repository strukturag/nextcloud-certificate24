# Makefile for building the project

app_name=esig

project_dir=$(CURDIR)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates
version+=master

PO_FILES=$(wildcard translationfiles/*/esig.po)
SRC_FILES=$(shell find src)
LIB_FILES=$(shell find lib)

all: dev-setup build-production

dev-setup: clean-dev composer-install-dev npm-init

release: appstore create-tag

build-dev: composer-install-dev build-js

build-production: composer-install-production build-js-production

composer-install-dev:
	composer install

composer-install-production:
	composer install --no-dev

build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

test:
	npm run test:unit

lint:
	npm run lint

lint-fix:
	npm run lint:fix

npm-init:
	npm ci

npm-update:
	npm update

translationtool.phar:
	curl -L -o translationtool.phar https://github.com/nextcloud/docker-ci/raw/master/translations/translationtool/translationtool.phar
	chmod a+x translationtool.phar

translationfiles/templates/esig.pot: translationtool.phar $(SRC_FILES) $(LIB_FILES)
	./translationtool.phar create-pot-files
	sed -i "s|$(CURDIR)/||" $@

po: $(PO_FILES) translationfiles/templates/esig.pot

translationfiles/%/esig.po: translationfiles/templates/esig.pot
	msgmerge --update $@ $<
	touch $@

.PHONY: l10n
l10n: translationtool.phar $(PO_FILES)
	./translationtool.phar convert-po-files

clean:
	rm -rf js/*
	rm -rf $(build_dir)

clean-dev: clean
	rm -rf node_modules
	rm -rf vendor

create-tag:
	git tag -a v$(version) -m "Tagging the $(version) release."
	git push origin v$(version)

appstore:
	rm -rf $(build_dir)
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=babel.config.js \
	--exclude=/build \
	--exclude=composer.json \
	--exclude=composer.lock \
	--exclude=/docker \
	--exclude=docs \
	--exclude=.dockerignore \
	--exclude=.drone.jsonnet \
	--exclude=.drone.yml \
	--exclude=.eslintignore \
	--exclude=.eslintrc.js \
	--exclude=.eslintrc.yml \
	--exclude=.git \
	--exclude=.gitattributes \
	--exclude=.github \
	--exclude=.gitignore \
	--exclude=jest.config.js \
	--exclude=.l10nignore \
	--exclude=login.txt \
	--exclude=*.code-workspace \
	--exclude=*.patch \
	--exclude=*.phar \
	--exclude=*.sh \
	--exclude=mkdocs.yml \
	--exclude=Makefile \
	--exclude=node_modules \
	--exclude=package.json \
	--exclude=package-lock.json \
	--exclude=.php-cs-fixer.cache \
	--exclude=.php-cs-fixer.dist.php \
	--exclude=.php_cs.cache \
	--exclude=.php_cs.dist \
	--exclude=psalm.xml \
	--exclude=README.md \
	--exclude=/src \
	--exclude=.stylelintignore \
	--exclude=stylelint.config.js \
	--exclude=.tx \
	--exclude=tests \
	--exclude=tsconfig.json \
	--exclude=translationfiles \
	--exclude=vendor \
	--exclude=vendor-bin \
	--exclude=webpack.js \
	$(project_dir)/  $(sign_dir)/$(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing app files…"; \
		php ../../../nextcloud/server/occ integrity:sign-app \
			--privateKey=$(cert_dir)/$(app_name).key\
			--certificate=$(cert_dir)/$(app_name).crt\
			--path=$(sign_dir)/$(app_name); \
	fi
	tar -czf $(build_dir)/$(app_name).tar.gz \
		-C $(sign_dir) $(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing package…"; \
		openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(build_dir)/$(app_name).tar.gz | openssl base64; \
	fi
