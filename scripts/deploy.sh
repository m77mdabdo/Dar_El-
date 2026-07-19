#!/usr/bin/env bash
#
# Dar El Jamila — unified production deploy script.
#
# Run manually via SSH, from the Laravel project directory on the server
# (i.e. public_html/laravel/ — one level BELOW the real web root,
# public_html/, which is where Apache actually serves static files from):
#
#   cd public_html/laravel && bash scripts/deploy.sh
#
# Replaces the multi-step manual process (git pull, composer install,
# extract build.zip, copy static files up to public_html/, migrate, cache)
# that this project's history shows gets partially forgotten almost every
# time — most recently: sw.js, site.webmanifest, and a stale build.zip all
# lagging behind a "deployed" commit. See the repo memory notes on
# production_hosting_file_structure and build_zip_deploy_process for the
# incidents this script exists to stop repeating.
#
# Fails loudly and stops on the first error (set -e below) — it does not
# push through a broken step. Every major step is echoed so a failure's
# exact location is obvious in the SSH session output.

set -euo pipefail

# ---------------------------------------------------------------------
# Config — edit this list as new root-level static files show up. Each
# entry is a path relative to public/, copied to the same relative path
# under the real web root (../ from here, i.e. public_html/). Directories
# are copied recursively. This is exactly the class of file that has
# repeatedly gone stale in production because a plain `git pull` only
# updates this project's own public/, never the separate public_html/
# root Apache actually serves from.
# ---------------------------------------------------------------------
STATIC_SYNC_ITEMS=(
    "favicon.ico"
    "site.webmanifest"
    "sw.js"
    "robots.txt"
    "assets"
    "build"
    "BingSiteAuth.xml"
)

WEB_ROOT=".."   # public_html/, one level above this Laravel project

# ---------------------------------------------------------------------

step() {
    echo ""
    echo "==> $1"
}

fail() {
    echo ""
    echo "!! DEPLOY FAILED: $1"
    echo "!! Stopping — nothing after this step ran."
    exit 1
}

trap 'fail "unexpected error on line $LINENO"' ERR

if [ ! -f artisan ]; then
    fail "artisan not found in the current directory — run this from the Laravel project root (public_html/laravel/), not $(pwd)."
fi

if [ ! -d "$WEB_ROOT" ]; then
    fail "WEB_ROOT (\"$WEB_ROOT\", resolves to $(pwd)/$WEB_ROOT) doesn't exist — check the WEB_ROOT setting at the top of this script."
fi

echo "======================================================"
echo " Dar El Jamila deploy — $(date '+%Y-%m-%d %H:%M:%S %Z')"
echo "======================================================"
echo "Project dir: $(pwd)"
echo "Web root:    $(cd "$WEB_ROOT" && pwd)"
echo "(Confirm the web root above is really public_html/ before trusting this run.)"

COMMIT_BEFORE=$(git rev-parse --short HEAD)
LOCK_HASH_BEFORE=$(md5sum composer.lock 2>/dev/null | awk '{print $1}' || echo "none")

# ---------------------------------------------------------------------
step "1/8 — git pull origin main"
# ---------------------------------------------------------------------
git pull origin main || fail "git pull failed — resolve locally before re-running."

COMMIT_AFTER=$(git rev-parse --short HEAD)

if [ "$COMMIT_BEFORE" = "$COMMIT_AFTER" ]; then
    echo "Already up to date at $COMMIT_AFTER."
else
    echo "Updated: $COMMIT_BEFORE -> $COMMIT_AFTER"
fi

# ---------------------------------------------------------------------
step "2/8 — composer install"
# ---------------------------------------------------------------------
LOCK_HASH_AFTER=$(md5sum composer.lock 2>/dev/null | awk '{print $1}' || echo "none")

if [ "$LOCK_HASH_BEFORE" = "$LOCK_HASH_AFTER" ]; then
    echo "composer.lock unchanged — skipping composer install."
    COMPOSER_RAN="skipped (composer.lock unchanged)"
else
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
        || fail "composer install failed."
    COMPOSER_RAN="ran (composer.lock changed)"
fi

# ---------------------------------------------------------------------
step "3/8 — extract public/build.zip into public/build/"
# ---------------------------------------------------------------------
if [ ! -f public/build.zip ]; then
    fail "public/build.zip not found — was it committed in this deploy? (only needed if app.css/app.js changed)."
fi

rm -rf public/build
unzip -q -o public/build.zip -d public/ || fail "failed to extract public/build.zip."

if [ ! -f public/build/manifest.json ]; then
    fail "public/build/manifest.json missing after extraction — build.zip looks corrupt or was zipped from the wrong directory."
fi

echo "Extracted $(find public/build -type f | wc -l | tr -d ' ') file(s) into public/build/."

# ---------------------------------------------------------------------
step "4/8 — sync static files to the real web root ($WEB_ROOT)"
# ---------------------------------------------------------------------
SYNCED_ITEMS=()

for item in "${STATIC_SYNC_ITEMS[@]}"; do
    src="public/$item"
    dest="$WEB_ROOT/$item"

    if [ ! -e "$src" ]; then
        echo "  skip: $src (doesn't exist in this build — not an error, just not present)"
        continue
    fi

    if [ -d "$src" ]; then
        mkdir -p "$dest"
        cp -a "$src/." "$dest/" || fail "failed to sync directory $src -> $dest"
    else
        cp -a "$src" "$dest" || fail "failed to sync file $src -> $dest"
    fi

    echo "  synced: $src -> $dest"
    SYNCED_ITEMS+=("$item")
done

# Google site-verification files carry a random token in the filename
# (google<hash>.html) that changes whenever a new one is issued — globbed
# rather than hardcoded. (Bing's equivalent, BingSiteAuth.xml, has no such
# random component, so it's a plain entry in STATIC_SYNC_ITEMS above — a
# literal filename isn't a glob pattern, nullglob has no effect on it.)
shopt -s nullglob
for verification_file in public/google*.html; do
    name=$(basename "$verification_file")
    cp -a "$verification_file" "$WEB_ROOT/$name" || fail "failed to sync verification file $name"
    echo "  synced: public/$name -> $WEB_ROOT/$name"
    SYNCED_ITEMS+=("$name")
done
shopt -u nullglob

if [ ${#SYNCED_ITEMS[@]} -eq 0 ]; then
    echo "  (nothing matched — check STATIC_SYNC_ITEMS and public/ contents if this is unexpected)"
fi

# ---------------------------------------------------------------------
step "5/8 — php artisan migrate --force"
# ---------------------------------------------------------------------
MIGRATE_OUTPUT=$(php artisan migrate --force 2>&1) || fail "migration failed:
$MIGRATE_OUTPUT"
echo "$MIGRATE_OUTPUT"

if echo "$MIGRATE_OUTPUT" | grep -qi "Nothing to migrate"; then
    MIGRATIONS_RAN="none pending"
else
    MIGRATIONS_RAN="ran"
fi

# ---------------------------------------------------------------------
step "6/8 — php artisan optimize:clear"
# ---------------------------------------------------------------------
php artisan optimize:clear || fail "optimize:clear failed."

# ---------------------------------------------------------------------
step "7/8 — rebuild caches (config, route, view)"
# ---------------------------------------------------------------------
php artisan config:cache || fail "config:cache failed."
php artisan route:cache || fail "route:cache failed."
php artisan view:cache || fail "view:cache failed."

# ---------------------------------------------------------------------
step "8/8 — summary"
# ---------------------------------------------------------------------
echo "======================================================"
echo " Deploy complete — $(date '+%Y-%m-%d %H:%M:%S %Z')"
echo "======================================================"
echo "Commit:        $COMMIT_BEFORE -> $COMMIT_AFTER"
echo "Composer:      $COMPOSER_RAN"
echo "Build assets:  extracted and synced to $WEB_ROOT/build/"
echo "Static files synced (${#SYNCED_ITEMS[@]}):"
for item in "${SYNCED_ITEMS[@]}"; do
    echo "  - $item"
done
echo "Migrations:    $MIGRATIONS_RAN"
echo "Caches:        config, route, view rebuilt"
echo "======================================================"
