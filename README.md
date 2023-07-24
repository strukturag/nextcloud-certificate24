# nextcloud-certificate24

This repository contains the Nextcloud app for Certificate24.

With Certificate24 you can request digital signatures of documents stored in
your Nextcloud from other users in Nextcloud or from external users (by email).


Checkout the repository as `certificate24` to the `apps` folder of your
Nextcloud instance.

## Prerequisites

- [composer](https://getcomposer.org/) 2.4
- [node](https://nodejs.org/) 16 with npm 7 or 8
- Nextcloud 24+


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

Run `make l10n` to update the translation files. Translations can be changed
using [poedit](https://poedit.net/) or similar tools in the `translationfiles`
folder. Make sure to run `make l10n` afterwards to update the files necessary
for Nextcloud.
