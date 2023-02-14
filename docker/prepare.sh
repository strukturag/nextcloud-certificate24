#!/bin/bash
set -e

# Let /entrypoint.sh not run apache
tail -1 /entrypoint.sh | grep exec && sed -i '$ d' /entrypoint.sh

# Launch canonical entrypoint
/entrypoint.sh $@

# Function from the Nextcloud's original entrypoint
run_as() {
    if [ "$(id -u)" = 0 ]; then
        su -p www-data -s /bin/sh -c "$1"
    else
        sh -c "$1"
    fi
}

rsync -rlDog --delete --chown www-data:www-data /usr/src/nextcloud/custom_apps/esig/ /var/www/html/custom_apps/esig/

run_as 'php /var/www/html/occ config:system:set appstoreenabled --type boolean --value false'
run_as 'php /var/www/html/occ config:system:set integrity.check.disabled --type boolean --value true'

run_as 'php /var/www/html/occ upgrade'
run_as 'php /var/www/html/occ app:enable esig'
run_as 'php /var/www/html/occ app:update esig'

if [ -n "$NEXTCLOUD_ADMIN_DISPLAY_NAME" ]; then
    run_as 'php /var/www/html/occ user:setting --update-only "${NEXTCLOUD_ADMIN_USER}" settings display_name "${NEXTCLOUD_ADMIN_DISPLAY_NAME}"' || true
fi
if [ -n "$NEXTCLOUD_ADMIN_EMAIL" ]; then
    run_as 'php /var/www/html/occ user:setting "${NEXTCLOUD_ADMIN_USER}" settings email "${NEXTCLOUD_ADMIN_EMAIL}"' || true
fi
if [ -n "$OVERWRITEHOST" ]; then
    run_as 'php /var/www/html/occ config:system:set overwritehost --value "${OVERWRITEHOST}"'
fi
if [ -n "$OVERWRITECLIURL" ]; then
    run_as 'php /var/www/html/occ config:system:set overwrite.cli.url --value "${OVERWRITECLIURL}"'
fi

if [ -n "$ESIG_ACCOUNT_ID" ] && [ -n "$ESIG_ACCOUNT_SECRET" ]; then
    run_as 'php /var/www/html/occ config:app:set esig account --value "{\"id\": \"${ESIG_ACCOUNT_ID}\",\"secret\": \"${ESIG_ACCOUNT_SECRET}\"}"'
fi

UPDATE_THEME=
run_as 'php /var/www/html/occ config:app:set theming enabled --value yes'
run_as 'php /var/www/html/occ config:app:set theming name --value "${NEXTCLOUD_THEMING_NAME}"'
run_as 'php /var/www/html/occ config:app:set theming url --value "${NEXTCLOUD_THEMING_URL}"'
run_as 'php /var/www/html/occ config:app:set theming slogan --value "${NEXTCLOUD_THEMING_SLOGAN}"'
if [ -n "$NEXTCLOUD_THEMING_COLOR" ]; then
    run_as 'php /var/www/html/occ config:app:set theming color --value "${NEXTCLOUD_THEMING_COLOR}"'
    UPDATE_THEME=1
fi

if [ -n "$UPDATE_THEME" ]; then
    run_as 'php /var/www/html/occ maintenance:theme:update'
fi

# Run the server
exec "$@"
