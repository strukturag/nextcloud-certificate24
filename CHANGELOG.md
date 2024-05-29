# Changelog

All notable changes to this project will be documented in this file.

## 0.2.4 - 2024-05-29

### Changed
- Bump @nextcloud/browserslist-config from 3.0.0 to 3.0.1
  [#355](https://github.com/strukturag/nextcloud-certificate24/pull/355)
- Bump @nextcloud/dialogs from 4.2.6 to 4.2.7
  [#347](https://github.com/strukturag/nextcloud-certificate24/pull/347)
- Use shorter filename if too long with signer name.
  [#374](https://github.com/strukturag/nextcloud-certificate24/pull/374)


## 0.2.3 - 2024-04-09

### Changed
- Bump @peculiar/x509 from 1.9.5 to 1.9.6
  [#278](https://github.com/strukturag/nextcloud-certificate24/pull/278)
- Bump @nextcloud/dialogs from 4.2.2 to 4.2.3
  [#294](https://github.com/strukturag/nextcloud-certificate24/pull/294)
- Bump @peculiar/x509 from 1.9.6 to 1.9.7
  [#301](https://github.com/strukturag/nextcloud-certificate24/pull/301)
- Bump @nextcloud/router from 2.2.0 to 2.2.1
  [#296](https://github.com/strukturag/nextcloud-certificate24/pull/296)
- Bump @nextcloud/dialogs from 4.2.3 to 4.2.6
  [#318](https://github.com/strukturag/nextcloud-certificate24/pull/318)
- Bump @nextcloud/webpack-vue-config from 6.0.0 to 6.0.1
  [#303](https://github.com/strukturag/nextcloud-certificate24/pull/303)
- Bump vue from 2.7.15 to 2.7.16
- Increase request timeout to 300s and make configurable.

### Fixed
- Make sure filenames of signed files are safe for filesystems.
- Only define "str_to_stream" if it doesn't exist yet.
- Add missing cast to always return an int.


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
