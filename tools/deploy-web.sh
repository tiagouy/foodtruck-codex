#!/usr/bin/env bash
# Run from a private clone on the production server, never inside public_html.
set -euo pipefail
umask 077
release_ref=${1:?Usage: bash tools/deploy-web.sh RELEASE_TAG}
site=/home/usefsgir/public_html/foodtruckuruguay.com
backup_root=/home/usefsgir/foodtruck-deploy-backups
php_bin=/opt/alt/php83/usr/bin/php
wp_bin=/usr/local/sbin/wp
repo=$(git rev-parse --show-toplevel)
case "$repo" in /home/usefsgir/public_html|/home/usefsgir/public_html/*) echo 'Clone must be outside public_html.' >&2; exit 1;; esac
[[ "$release_ref" == foodtrucks-uy-core-v* ]] || { echo 'Expected a plugin release tag.' >&2; exit 1; }
[[ -f "$site/wp-config.php" && -x "$php_bin" ]] || exit 1
for destination in "$site/wp-content/plugins/foodtrucks-uy-core" "$site/wp-content/themes/foodtrucks-astra"; do
    [[ -d "$destination" && ! -L "$destination" ]] || { echo 'Missing or symlinked destination.' >&2; exit 1; }
done
git fetch origin --tags
commit=$(git rev-parse --verify "refs/tags/$release_ref^{commit}")
mkdir -p "$backup_root"
chmod 700 "$backup_root"
snapshot=$(mktemp -d "$backup_root/release-XXXXXXXX")
mkdir "$snapshot/stage"
git archive "$commit" plugins/foodtrucks-uy-core themes/foodtrucks-astra | tar -xf - -C "$snapshot/stage"
while IFS= read -r -d '' source; do "$php_bin" -l "$source" >/dev/null; done < <(find "$snapshot/stage" -type f -name '*.php' -print0)
"$php_bin" "$wp_bin" --path="$site" --skip-plugins --skip-themes db export "$snapshot/database.sql"
git -C "$repo" rev-parse "$commit" > "$snapshot/commit.txt"
printf '%s\n' "$release_ref" > "$snapshot/tag.txt"
plugin_moved=0; theme_moved=0
rollback_files() {
    exit_status=$?
    if (( exit_status != 0 )); then
        echo "Deployment failed. Restoring code; database backup kept at $snapshot/database.sql" >&2
        if (( plugin_moved )); then
            if [[ -d "$site/wp-content/plugins/foodtrucks-uy-core" ]]; then mv "$site/wp-content/plugins/foodtrucks-uy-core" "$snapshot/failed-plugin"; fi
            mv "$snapshot/old-plugin" "$site/wp-content/plugins/foodtrucks-uy-core"
        fi
        if (( theme_moved )); then
            if [[ -d "$site/wp-content/themes/foodtrucks-astra" ]]; then mv "$site/wp-content/themes/foodtrucks-astra" "$snapshot/failed-theme"; fi
            mv "$snapshot/old-theme" "$site/wp-content/themes/foodtrucks-astra"
        fi
    fi
}
trap rollback_files EXIT
mv "$site/wp-content/plugins/foodtrucks-uy-core" "$snapshot/old-plugin"
plugin_moved=1
mv "$snapshot/stage/plugins/foodtrucks-uy-core" "$site/wp-content/plugins/foodtrucks-uy-core"
mv "$site/wp-content/themes/foodtrucks-astra" "$snapshot/old-theme"
theme_moved=1
mv "$snapshot/stage/themes/foodtrucks-astra" "$site/wp-content/themes/foodtrucks-astra"
# Public code must be readable by the web server; backups remain private.
find "$site/wp-content/plugins/foodtrucks-uy-core" "$site/wp-content/themes/foodtrucks-astra" -type d -exec chmod 755 {} +
find "$site/wp-content/plugins/foodtrucks-uy-core" "$site/wp-content/themes/foodtrucks-astra" -type f -exec chmod 644 {} +
"$php_bin" "$wp_bin" --path="$site" eval 'echo "Plugin: " . FOODTRUCKS_UY_CORE_VERSION . PHP_EOL;'
for route in / /eventos/ /wp-json/foodtrucks-uy/v1/events; do
    status=$(curl -sS --max-time 30 -o /dev/null -w '%{http_code}' "https://foodtruckuruguay.com$route")
    [[ "$status" == 200 ]] || { echo "HTTP $status at $route" >&2; exit 1; }
done
trap - EXIT
echo "Deployed $release_ref ($commit). Backup: $snapshot"
echo 'Verify the admin, images and pages manually. Do not import the database backup unless explicitly recovering data.'
