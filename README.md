![Build Status](https://github.com/strukturag/nextcloud-certificate24/actions/workflows/phpunit.yml/badge.svg)
[![Coverage Status](https://codecov.io/gh/strukturag/nextcloud-certificate24/graph/badge.svg?token=XJS5ZGTZPW)](https://codecov.io/gh/strukturag/nextcloud-certificate24)

# nextcloud-certificate24

This repository contains the Nextcloud app for Certificate24.

With Certificate24 you can request digital signatures of documents stored in
your Nextcloud from other users in Nextcloud or from external users (by email).


Checkout the repository as `certificate24` to the `apps` folder of your
Nextcloud instance.

## Prerequisites

- [composer](https://getcomposer.org/) 2.4
- [node](https://nodejs.org/) 16 with npm 7 or 8
- Nextcloud 28+


## Build

	npm ci
	npm run build
	composer install


## Installation

Open the Apps settings of Nextcloud and enable the "Certificate24" app. Then go
to the "Administration settings", open section "Certificate24" and enter the
account id and account secret you created on the Certificate24 portal at
https://www.certificate24.com.


## Translations

Run `make translationfiles/templates/certificate24.pot` to update the
translation template.

We are using transifex to manage translations. If you want to contribute,
open https://explore.transifex.com/strukturag/nextcloud-certificate24/ to
join the project.
