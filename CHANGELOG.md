# Changelog

All notable changes to this project will be documented in this file.

## 0.3.5 - 2024-12-05

### Added
- Support date fields.
  [#469](https://github.com/strukturag/nextcloud-certificate24/pull/469)

### Dependencies
- Bump firebase/php-jwt from 6.10.1 to 6.10.2
  [#468](https://github.com/strukturag/nextcloud-certificate24/pull/468)
- Bump cross-spawn from 7.0.3 to 7.0.6
  [#466](https://github.com/strukturag/nextcloud-certificate24/pull/466)
- Bump @nextcloud/files from 3.9.0 to 3.10.0
  [#464](https://github.com/strukturag/nextcloud-certificate24/pull/464)
- Bump elliptic from 6.5.7 to 6.6.0
  [#462](https://github.com/strukturag/nextcloud-certificate24/pull/462)
- Bump @babel/preset-typescript from 7.25.7 to 7.26.0
  [#461](https://github.com/strukturag/nextcloud-certificate24/pull/461)
- Bump debounce from 2.1.1 to 2.2.0
  [#458](https://github.com/strukturag/nextcloud-certificate24/pull/458)
- Bump nextcloud/coding-standard from 1.3.1 to 1.3.2 in /vendor-bin/csfixer
  [#456](https://github.com/strukturag/nextcloud-certificate24/pull/456)
- Bump @nextcloud/webpack-vue-config from 6.1.1 to 6.2.0
  [#455](https://github.com/strukturag/nextcloud-certificate24/pull/455)
- Bump nextcloud from 29-apache to 30-apache in /docker
  [#449](https://github.com/strukturag/nextcloud-certificate24/pull/449)
- docker: Update to node 22.
  [#470](https://github.com/strukturag/nextcloud-certificate24/pull/470)
- Bump @nextcloud/vue from 8.19.0 to 8.21.0
  [#467](https://github.com/strukturag/nextcloud-certificate24/pull/467)
- Update @nextcloud/dialogs to 5.3.8


## 0.3.4 - 2024-10-09

### Added
- Implement "IWebhookCompatibleEvent" on Nextcloud 30.
  [#454](https://github.com/strukturag/nextcloud-certificate24/pull/454)

### Changed
- Updates for file translationfiles/templates/certificate24.pot in fr_FR
  [#440](https://github.com/strukturag/nextcloud-certificate24/pull/440)
- CI: Test against Nextcloud 30.
  [#446](https://github.com/strukturag/nextcloud-certificate24/pull/446)
- Increase test coverage
  [#447](https://github.com/strukturag/nextcloud-certificate24/pull/447)

### Fixed
- Fixed error in "TranslatedTemplate" on Nextcloud 30
  [#441](https://github.com/strukturag/nextcloud-certificate24/issues/441)

### Dependencies
- Bump @peculiar/x509 from 1.11.0 to 1.12.3
  [#439](https://github.com/strukturag/nextcloud-certificate24/pull/439)
- Bump body-parser and express
  [#438](https://github.com/strukturag/nextcloud-certificate24/pull/438)
- Bump send and express
  [#434](https://github.com/strukturag/nextcloud-certificate24/pull/434)
- Bump vimeo/psalm from 5.25.0 to 5.26.1 in /vendor-bin/psalm
  [#430](https://github.com/strukturag/nextcloud-certificate24/pull/430)
- Bump micromatch from 4.0.5 to 4.0.8
  [#428](https://github.com/strukturag/nextcloud-certificate24/pull/428)
- Bump fast-xml-parser from 4.2.7 to 4.4.1
  [#414](https://github.com/strukturag/nextcloud-certificate24/pull/414)
- Bump @nextcloud/auth from 2.3.0 to 2.4.0
  [#421](https://github.com/strukturag/nextcloud-certificate24/pull/421)
- Bump axios from 1.7.2 to 1.7.7
  [#442](https://github.com/strukturag/nextcloud-certificate24/pull/442)
- Bump cookie and express
  [#443](https://github.com/strukturag/nextcloud-certificate24/pull/443)
- Bump webpack from 5.88.2 to 5.95.0
  [#444](https://github.com/strukturag/nextcloud-certificate24/pull/444)
- Bump elliptic from 6.5.4 to 6.5.7
  [#445](https://github.com/strukturag/nextcloud-certificate24/pull/445)
- Bump debounce from 2.1.0 to 2.1.1
  [#432](https://github.com/strukturag/nextcloud-certificate24/pull/432)
- Bump nextcloud/coding-standard from 1.2.1 to 1.3.1 in /vendor-bin/csfixer
  [#435](https://github.com/strukturag/nextcloud-certificate24/pull/435)
- Bump @nextcloud/files from 3.6.0 to 3.9.0
  [#429](https://github.com/strukturag/nextcloud-certificate24/pull/429)
- Bump @nextcloud/vue from 8.14.0 to 8.19.0
  [#436](https://github.com/strukturag/nextcloud-certificate24/pull/436)
- Bump @nextcloud/dialogs from 5.3.5 to 5.3.7
  [#422](https://github.com/strukturag/nextcloud-certificate24/pull/422)
- Bump @nextcloud/webpack-vue-config from 6.0.1 to 6.1.1
  [#450](https://github.com/strukturag/nextcloud-certificate24/pull/450)
- Bump @nextcloud/axios from 2.5.0 to 2.5.1
  [#451](https://github.com/strukturag/nextcloud-certificate24/pull/451)
- Bump @babel/preset-typescript from 7.24.7 to 7.25.7
  [#452](https://github.com/strukturag/nextcloud-certificate24/pull/452)
- Bump vue-material-design-icons from 5.3.0 to 5.3.1
  [#453](https://github.com/strukturag/nextcloud-certificate24/pull/453)


## 0.3.3 - 2024-07-23

### Added
- Add button to check account settings on admin page.
  [#404](https://github.com/strukturag/nextcloud-certificate24/pull/404)
- Add background job to send out daily reminders for missing email signatures.
  [#408](https://github.com/strukturag/nextcloud-certificate24/pull/408)
- Support upcoming Nextcloud 30
  [#409](https://github.com/strukturag/nextcloud-certificate24/pull/409)

### Changed
- Bump vimeo/psalm from 5.24.0 to 5.25.0 in /vendor-bin/psalm
  [#392](https://github.com/strukturag/nextcloud-certificate24/pull/392)
- Bump friendsofphp/php-cs-fixer from 3.57.2 to 3.59.3 in /vendor-bin/csfixer
  [#391](https://github.com/strukturag/nextcloud-certificate24/pull/391)
- Bump braces from 3.0.2 to 3.0.3
  [#389](https://github.com/strukturag/nextcloud-certificate24/pull/389)
- Bump ws from 8.13.0 to 8.17.1
  [#388](https://github.com/strukturag/nextcloud-certificate24/pull/388)
- Bump @babel/preset-typescript from 7.24.1 to 7.24.7
  [#385](https://github.com/strukturag/nextcloud-certificate24/pull/385)
- Bump @nextcloud/eslint-config from 8.3.0 to 8.4.1
  [#382](https://github.com/strukturag/nextcloud-certificate24/pull/382)
- Bump debounce from 2.0.0 to 2.1.0
  [#379](https://github.com/strukturag/nextcloud-certificate24/pull/379)
- Bump @nextcloud/dialogs from 5.3.1 to 5.3.5
  [#399](https://github.com/strukturag/nextcloud-certificate24/pull/399)
- Bump @nextcloud/files from 3.3.1 to 3.6.0
  [#401](https://github.com/strukturag/nextcloud-certificate24/pull/401)
- Bump @peculiar/x509 from 1.9.7 to 1.11.0
  [#384](https://github.com/strukturag/nextcloud-certificate24/pull/384)
- Bump @nextcloud/vue from 8.11.2 to 8.14.0
  [#398](https://github.com/strukturag/nextcloud-certificate24/pull/398)
- Bump nextcloud/coding-standard from 1.1.0 to 1.2.1 in /vendor-bin/csfixer
  [#390](https://github.com/strukturag/nextcloud-certificate24/pull/390)
- Disable files menu / sidebar if not configured.
  [#405](https://github.com/strukturag/nextcloud-certificate24/pull/405)
- Updates for file translationfiles/templates/certificate24.pot in de
  [#407](https://github.com/strukturag/nextcloud-certificate24/pull/407)
- Updates for file translationfiles/templates/certificate24.pot in de_DE
  [#406](https://github.com/strukturag/nextcloud-certificate24/pull/406)
- Updates for file translationfiles/templates/certificate24.pot in de
  [#410](https://github.com/strukturag/nextcloud-certificate24/pull/410)
- Updates for file translationfiles/templates/certificate24.pot in de_DE
  [#411](https://github.com/strukturag/nextcloud-certificate24/pull/411)

### Fixed
- Fix path in require_once for csfixer.
  [#403](https://github.com/strukturag/nextcloud-certificate24/pull/403)


## 0.3.2 - 2024-05-29

### Changed
- Bump friendsofphp/php-cs-fixer from 3.52.1 to 3.53.0 in /vendor-bin/csfixer
  [#344](https://github.com/strukturag/nextcloud-certificate24/pull/344)
- Bump @nextcloud/vue from 8.11.1 to 8.11.2
  [#345](https://github.com/strukturag/nextcloud-certificate24/pull/345)
- Bump @nextcloud/dialogs from 5.2.0 to 5.3.0
  [#346](https://github.com/strukturag/nextcloud-certificate24/pull/346)
- Bump firebase/php-jwt from 6.10.0 to 6.10.1
  [#371](https://github.com/strukturag/nextcloud-certificate24/pull/371)
- Bump friendsofphp/php-cs-fixer from 3.53.0 to 3.57.2 in /vendor-bin/csfixer
  [#370](https://github.com/strukturag/nextcloud-certificate24/pull/370)
- Bump vimeo/psalm from 5.23.1 to 5.24.0 in /vendor-bin/psalm
  [#362](https://github.com/strukturag/nextcloud-certificate24/pull/362)
- Bump @nextcloud/browserslist-config from 3.0.0 to 3.0.1
  [#354](https://github.com/strukturag/nextcloud-certificate24/pull/354)
- Bump @nextcloud/dialogs from 5.3.0 to 5.3.1
  [#349](https://github.com/strukturag/nextcloud-certificate24/pull/349)
- Bump @nextcloud/router from 3.0.0 to 3.0.1
  [#352](https://github.com/strukturag/nextcloud-certificate24/pull/352)
- Bump @nextcloud/babel-config from 1.0.0 to 1.2.0
  [#368](https://github.com/strukturag/nextcloud-certificate24/pull/368)
- Bump @nextcloud/stylelint-config from 2.4.0 to 3.0.1
  [#364](https://github.com/strukturag/nextcloud-certificate24/pull/364)
- Use shorter filename if too long with signer name.
  [#374](https://github.com/strukturag/nextcloud-certificate24/pull/374)
- Bump @nextcloud/l10n from 2.2.0 to 3.1.0
  [#365](https://github.com/strukturag/nextcloud-certificate24/pull/365)
- Bump @nextcloud/axios from 2.4.0 to 2.5.0
  [#361](https://github.com/strukturag/nextcloud-certificate24/pull/361)
- Bump @nextcloud/initial-state from 2.1.0 to 2.2.0
  [#359](https://github.com/strukturag/nextcloud-certificate24/pull/359)
- Bump @nextcloud/files from 3.1.1 to 3.3.1
  [#372](https://github.com/strukturag/nextcloud-certificate24/pull/372)


## 0.3.1 - 2024-04-09

### Added
- Support Nextcloud 29
  [#337](https://github.com/strukturag/nextcloud-certificate24/pull/337)

### Changed
- Bump friendsofphp/php-cs-fixer from 3.41.1 to 3.45.0 in /vendor-bin/csfixer
  [#282](https://github.com/strukturag/nextcloud-certificate24/pull/282)
- Bump vimeo/psalm from 5.17.0 to 5.18.0 in /vendor-bin/psalm
  [#277](https://github.com/strukturag/nextcloud-certificate24/pull/277)
- Bump @peculiar/x509 from 1.9.5 to 1.9.6
  [#276](https://github.com/strukturag/nextcloud-certificate24/pull/276)
- Bump friendsofphp/php-cs-fixer from 3.45.0 to 3.46.0 in /vendor-bin/csfixer
  [#286](https://github.com/strukturag/nextcloud-certificate24/pull/286)
- Bump axios from 1.4.0 to 1.6.3
  [#285](https://github.com/strukturag/nextcloud-certificate24/pull/285)
- Bump follow-redirects from 1.15.2 to 1.15.4
  [#287](https://github.com/strukturag/nextcloud-certificate24/pull/287)
- Bump vimeo/psalm from 5.18.0 to 5.19.0 in /vendor-bin/psalm
  [#288](https://github.com/strukturag/nextcloud-certificate24/pull/288)
- Bump @nextcloud/files from 3.0.0 to 3.1.0
  [#290](https://github.com/strukturag/nextcloud-certificate24/pull/290)
- Bump @nextcloud/moment from 1.2.2 to 1.3.1
  [#289](https://github.com/strukturag/nextcloud-certificate24/pull/289)
- Bump phpunit/phpunit from 9.6.15 to 9.6.16 in /vendor-bin/phpunit
  [#295](https://github.com/strukturag/nextcloud-certificate24/pull/295)
- Bump @nextcloud/dialogs from 5.0.3 to 5.1.0
  [#293](https://github.com/strukturag/nextcloud-certificate24/pull/293)
- Bump vimeo/psalm from 5.19.0 to 5.20.0 in /vendor-bin/psalm
  [#292](https://github.com/strukturag/nextcloud-certificate24/pull/292)
- Bump friendsofphp/php-cs-fixer from 3.46.0 to 3.48.0 in /vendor-bin/csfixer
  [#291](https://github.com/strukturag/nextcloud-certificate24/pull/291)
- Bump friendsofphp/php-cs-fixer from 3.48.0 to 3.52.1 in /vendor-bin/csfixer
  [#332](https://github.com/strukturag/nextcloud-certificate24/pull/332)
- Bump follow-redirects from 1.15.4 to 1.15.6
  [#327](https://github.com/strukturag/nextcloud-certificate24/pull/327)
- Bump express from 4.18.2 to 4.19.2
  [#334](https://github.com/strukturag/nextcloud-certificate24/pull/334)
- Bump phpunit/phpunit from 9.6.16 to 9.6.18 in /vendor-bin/phpunit
  [#331](https://github.com/strukturag/nextcloud-certificate24/pull/331)
- Bump vimeo/psalm from 5.20.0 to 5.23.1 in /vendor-bin/psalm
  [#325](https://github.com/strukturag/nextcloud-certificate24/pull/325)
- Bump @nextcloud/stylelint-config from 2.3.1 to 2.4.0
  [#297](https://github.com/strukturag/nextcloud-certificate24/pull/297)
- Bump webpack-dev-middleware from 5.3.3 to 5.3.4
  [#333](https://github.com/strukturag/nextcloud-certificate24/pull/333)
- Bump @babel/preset-typescript from 7.23.3 to 7.24.1
  [#329](https://github.com/strukturag/nextcloud-certificate24/pull/329)
- Bump vue-material-design-icons from 5.2.0 to 5.3.0
  [#308](https://github.com/strukturag/nextcloud-certificate24/pull/308)
- Bump @peculiar/x509 from 1.9.6 to 1.9.7
  [#306](https://github.com/strukturag/nextcloud-certificate24/pull/306)
- Bump @nextcloud/files from 3.1.0 to 3.1.1
  [#328](https://github.com/strukturag/nextcloud-certificate24/pull/328)
- Bump phpunit/phpunit from 9.6.18 to 9.6.19 in /vendor-bin/phpunit
  [#335](https://github.com/strukturag/nextcloud-certificate24/pull/335)
- Bump @nextcloud/webpack-vue-config from 6.0.0 to 6.0.1
  [#304](https://github.com/strukturag/nextcloud-certificate24/pull/304)
- Bump @nextcloud/router from 2.2.0 to 3.0.0
  [#307](https://github.com/strukturag/nextcloud-certificate24/pull/307)
- Bump @nextcloud/dialogs from 5.1.0 to 5.2.0
  [#324](https://github.com/strukturag/nextcloud-certificate24/pull/324)
- Increase request timeout to 300s and make configurable.
  [#338](https://github.com/strukturag/nextcloud-certificate24/pull/338)
- Bump vue from 2.7.15 to 2.7.16
  [#341](https://github.com/strukturag/nextcloud-certificate24/pull/341)
- Show relative timestamps on signatures overview page.
  [#340](https://github.com/strukturag/nextcloud-certificate24/pull/340)

### Fixed
- Make sure filenames of signed files are safe for filesystems.
  [#339](https://github.com/strukturag/nextcloud-certificate24/pull/339)
- Only define "str_to_stream" if it doesn't exist yet.
- Add missing cast to always return an int.
  [#342](https://github.com/strukturag/nextcloud-certificate24/pull/342)


## 0.3.0 - 2023-12-11

### Added
- Support Nextcloud 28
  [#270](https://github.com/strukturag/nextcloud-certificate24/pull/270)

### Changed
- Bump friendsofphp/php-cs-fixer from 3.41.0 to 3.41.1 in /vendor-bin/csfixer
  [#269](https://github.com/strukturag/nextcloud-certificate24/pull/269)
- Migrate to file action registration from "@nextcloud/files".
  [#271](https://github.com/strukturag/nextcloud-certificate24/pull/271)
- Bump @nextcloud/vue from 7.12.6 to 8.3.0
  [#264](https://github.com/strukturag/nextcloud-certificate24/pull/264)
- Bump @nextcloud/dialogs from 4.2.2 to 5.0.3
  [#272](https://github.com/strukturag/nextcloud-certificate24/pull/272)
- Remove "@nextcloud/vue-dashboard" and use native "@nextcloud/vue" components
  [#273](https://github.com/strukturag/nextcloud-certificate24/pull/273)
- Updates for new versions of Nextcloud vue components
  [#274](https://github.com/strukturag/nextcloud-certificate24/pull/274)


## 0.2.2 - 2023-12-11

### Added
- Style message about signature image upload required.
  [#220](https://github.com/strukturag/nextcloud-certificate24/pull/220)
- Always use white background for signatures in personal settings.
  [#238](https://github.com/strukturag/nextcloud-certificate24/pull/238)
- French translations (fr_FR)
  [#244](https://github.com/strukturag/nextcloud-certificate24/pull/244)

### Changed
- Updates for file translationfiles/templates/certificate24.pot in de on branch master
  [#221](https://github.com/strukturag/nextcloud-certificate24/pull/221)
- Updates for file translationfiles/templates/certificate24.pot in de_DE on branch master
  [#222](https://github.com/strukturag/nextcloud-certificate24/pull/222)
- Bump @babel/preset-typescript from 7.22.11 to 7.22.15
  [#224](https://github.com/strukturag/nextcloud-certificate24/pull/224)
- Bump friendsofphp/php-cs-fixer from 3.25.0 to 3.26.1 in /vendor-bin/csfixer
  [#223](https://github.com/strukturag/nextcloud-certificate24/pull/223)
- Bump phpunit/phpunit from 9.6.11 to 9.6.12 in /vendor-bin/phpunit
  [#225](https://github.com/strukturag/nextcloud-certificate24/pull/225)
- Bump friendsofphp/php-cs-fixer from 3.26.1 to 3.28.0 in /vendor-bin/csfixer
  [#231](https://github.com/strukturag/nextcloud-certificate24/pull/231)
- Bump phpunit/phpunit from 9.6.12 to 9.6.13 in /vendor-bin/phpunit
  [#230](https://github.com/strukturag/nextcloud-certificate24/pull/230)
- Bump friendsofphp/php-cs-fixer from 3.28.0 to 3.34.0 in /vendor-bin/csfixer
  [#235](https://github.com/strukturag/nextcloud-certificate24/pull/235)
- Bump @babel/preset-typescript from 7.22.15 to 7.23.0
  [#233](https://github.com/strukturag/nextcloud-certificate24/pull/233)
- Bump @nextcloud/eslint-config from 8.2.1 to 8.3.0
  [#232](https://github.com/strukturag/nextcloud-certificate24/pull/232)
- Bump firebase/php-jwt from 6.8.1 to 6.9.0
  [#237](https://github.com/strukturag/nextcloud-certificate24/pull/237)
- Bump friendsofphp/php-cs-fixer from 3.34.0 to 3.34.1 in /vendor-bin/csfixer
  [#236](https://github.com/strukturag/nextcloud-certificate24/pull/236)
- Bump @babel/preset-typescript from 7.23.0 to 7.23.2
  [#240](https://github.com/strukturag/nextcloud-certificate24/pull/240)
- Bump friendsofphp/php-cs-fixer from 3.34.1 to 3.35.1 in /vendor-bin/csfixer
  [#239](https://github.com/strukturag/nextcloud-certificate24/pull/239)
- Bump @nextcloud/vue from 7.12.4 to 7.12.6
  [#234](https://github.com/strukturag/nextcloud-certificate24/pull/234)
- Bump @nextcloud/auth from 2.1.0 to 2.2.1
  [#229](https://github.com/strukturag/nextcloud-certificate24/pull/229)
- Bump @babel/traverse from 7.23.0 to 7.23.2
  [#241](https://github.com/strukturag/nextcloud-certificate24/pull/241)
- Bump @nextcloud/dialogs from 4.1.0 to 4.2.1
  [#228](https://github.com/strukturag/nextcloud-certificate24/pull/228)
- Bump @nextcloud/moment from 1.2.1 to 1.2.2
  [#242](https://github.com/strukturag/nextcloud-certificate24/pull/242)
- Bump @nextcloud/router from 2.1.2 to 2.2.0
  [#243](https://github.com/strukturag/nextcloud-certificate24/pull/243)
- Bump browserify-sign from 4.2.1 to 4.2.2
  [#247](https://github.com/strukturag/nextcloud-certificate24/pull/247)
- Bump friendsofphp/php-cs-fixer from 3.35.1 to 3.36.0 in /vendor-bin/csfixer
  [#245](https://github.com/strukturag/nextcloud-certificate24/pull/245)
- Bump vue from 2.7.14 to 2.7.15
  [#246](https://github.com/strukturag/nextcloud-certificate24/pull/246)
- Bump friendsofphp/php-cs-fixer from 3.36.0 to 3.37.1 in /vendor-bin/csfixer
  [#248](https://github.com/strukturag/nextcloud-certificate24/pull/248)
- Bump @babel/preset-typescript from 7.23.2 to 7.23.3
  [#252](https://github.com/strukturag/nextcloud-certificate24/pull/252)
- Bump @nextcloud/dialogs from 4.2.1 to 4.2.2
  [#250](https://github.com/strukturag/nextcloud-certificate24/pull/250)
- Bump friendsofphp/php-cs-fixer from 3.37.1 to 3.38.0 in /vendor-bin/csfixer
  [#249](https://github.com/strukturag/nextcloud-certificate24/pull/249)
- Bump friendsofphp/php-cs-fixer from 3.38.0 to 3.38.2 in /vendor-bin/csfixer
  [#253](https://github.com/strukturag/nextcloud-certificate24/pull/253)
- Bump vimeo/psalm from 5.15.0 to 5.16.0 in /vendor-bin/psalm
  [#257](https://github.com/strukturag/nextcloud-certificate24/pull/257)
- Bump friendsofphp/php-cs-fixer from 3.38.2 to 3.39.1 in /vendor-bin/csfixer
  [#256](https://github.com/strukturag/nextcloud-certificate24/pull/256)
- Bump friendsofphp/php-cs-fixer from 3.39.1 to 3.40.0 in /vendor-bin/csfixer
  [#258](https://github.com/strukturag/nextcloud-certificate24/pull/258)
- Project has moved to the "strukturag" group.
- Updates for file translationfiles/templates/certificate24.pot in fr_FR
  [#244](https://github.com/strukturag/nextcloud-certificate24/pull/244)
- Updates for file translationfiles/templates/certificate24.pot in it
  [#261](https://github.com/strukturag/nextcloud-certificate24/pull/261)
- Updates for file translationfiles/templates/certificate24.pot in de
  [#260](https://github.com/strukturag/nextcloud-certificate24/pull/260)
- Updates for file translationfiles/templates/certificate24.pot in de_DE
  [#259](https://github.com/strukturag/nextcloud-certificate24/pull/259)
- Updates for file translationfiles/templates/certificate24.pot in fr_FR
  [#262](https://github.com/strukturag/nextcloud-certificate24/pull/262)
- Bump firebase/php-jwt from 6.9.0 to 6.10.0
  [#265](https://github.com/strukturag/nextcloud-certificate24/pull/265)
- Bump phpunit/phpunit from 9.6.13 to 9.6.15 in /vendor-bin/phpunit
  [#263](https://github.com/strukturag/nextcloud-certificate24/pull/263)
- Bump debounce from 1.2.1 to 2.0.0
  [#254](https://github.com/strukturag/nextcloud-certificate24/pull/254)
- Bump vimeo/psalm from 5.16.0 to 5.17.0 in /vendor-bin/psalm
  [#266](https://github.com/strukturag/nextcloud-certificate24/pull/266)
- Bump friendsofphp/php-cs-fixer from 3.40.0 to 3.41.0 in /vendor-bin/csfixer
  [#267](https://github.com/strukturag/nextcloud-certificate24/pull/267)
- Bump @nextcloud/vue from 7.12.6 to 7.12.7
  [#268](https://github.com/strukturag/nextcloud-certificate24/pull/268)


## 0.2.1 - 2023-09-06

### Changed
- Improve support for scanning encrypted documents.
  [#194](https://github.com/strukturag/nextcloud-certificate24/pull/194)
- Updates for file translationfiles/templates/certificate24.pot in de on branch master
  [#195](https://github.com/strukturag/nextcloud-certificate24/pull/195)
- Updates for file translationfiles/templates/certificate24.pot in it on branch master
  [#197](https://github.com/strukturag/nextcloud-certificate24/pull/197)
- Updates for file translationfiles/templates/certificate24.pot in de_DE on branch master
  [#196](https://github.com/strukturag/nextcloud-certificate24/pull/196)
- Bump @babel/preset-typescript from 7.22.5 to 7.22.11
  [#205](https://github.com/strukturag/nextcloud-certificate24/pull/205)
- Bump vimeo/psalm from 5.14.1 to 5.15.0 in /vendor-bin/psalm
  [#203](https://github.com/strukturag/nextcloud-certificate24/pull/203)
- Bump @nextcloud/webpack-vue-config from 5.5.1 to 6.0.0
  [#201](https://github.com/strukturag/nextcloud-certificate24/pull/201)
- Bump friendsofphp/php-cs-fixer from 3.22.0 to 3.23.0 in /vendor-bin/csfixer
  [#199](https://github.com/strukturag/nextcloud-certificate24/pull/199)
- Bump @nextcloud/vue from 7.12.1 to 7.12.4
  [#206](https://github.com/strukturag/nextcloud-certificate24/pull/206)
- Bump @nextcloud/browserslist-config from 2.3.0 to 3.0.0
  [#200](https://github.com/strukturag/nextcloud-certificate24/pull/200)
- Bump @peculiar/x509 from 1.9.3 to 1.9.5
  [#202](https://github.com/strukturag/nextcloud-certificate24/pull/202)
- Update node engines to next LTS (node 20 / npm 9)
  [#208](https://github.com/strukturag/nextcloud-certificate24/pull/208)
- Pass language / timezone of sender with signing request to backend.
  [#209](https://github.com/strukturag/nextcloud-certificate24/pull/209)
- Bump friendsofphp/php-cs-fixer from 3.23.0 to 3.25.0 in /vendor-bin/csfixer
  [#211](https://github.com/strukturag/nextcloud-certificate24/pull/211)
- Bump jquery from 3.7.0 to 3.7.1
  [#212](https://github.com/strukturag/nextcloud-certificate24/pull/212)
- CI: Test with stable27 branch of Nextcloud.
  [#213](https://github.com/strukturag/nextcloud-certificate24/pull/213)
- Delay validating previously failed files.
  [#210](https://github.com/strukturag/nextcloud-certificate24/pull/210)
- Updates for file translationfiles/templates/certificate24.pot in de on branch master
  [#214](https://github.com/strukturag/nextcloud-certificate24/pull/214)
- Updates for file translationfiles/templates/certificate24.pot in de_DE on branch master
  [#215](https://github.com/strukturag/nextcloud-certificate24/pull/215)
- Updates for file translationfiles/templates/certificate24.pot in it on branch master
  [#216](https://github.com/strukturag/nextcloud-certificate24/pull/216)
- Include package / composer lockfiles in release tarball.
  [#217](https://github.com/strukturag/nextcloud-certificate24/pull/217)
- composer: Update nextcloud/ocp to dev-stable27
  [#218](https://github.com/strukturag/nextcloud-certificate24/pull/218)

### Fixed
- Fix recipient properties when resending emails.
  [#193](https://github.com/strukturag/nextcloud-certificate24/pull/193)


## 0.2.0 - 2023-08-09

- First public release.
