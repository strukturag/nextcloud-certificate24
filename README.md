This repository contains the Nextcloud app for the esig service.

Checkout the repository as `esig` to the `apps` folder of your Nextcloud
instance.

## Prerequisites

- [composer](https://getcomposer.org/) 2.4
- [node](https://nodejs.org/) 16 with npm 7 or 8
- Nextcloud 24


## Build

	npm ci
	npm run build
	composer install


## Installation

Open the Apps settings of Nextcloud and enable the "eSignatures" app. Then go
to the "Administration settings", open section "eSignatures" and enter the
account id and account secret you created on the esig service portal.
