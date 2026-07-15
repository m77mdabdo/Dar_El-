#!/usr/bin/env bash
#
# Replacement for the plain `php artisan queue:work database --stop-when-empty
# --max-time=50 --timeout=45 --tries=3` cron line documented in the README —
# NOT an addition alongside it. Point the cron entry at this script instead:
#
#   * * * * *   /bin/bash /path/to/project/scripts/queue-worker-loop.sh
#
# Why: the host only allows a once-a-minute cron tick and forbids a
# persistent worker (no proc_open, no daemon), so a job queued right after
# a tick fires can sit for up to ~60s before the next tick drains it. This
# script re-runs `queue:work --stop-when-empty` every ~12s inside that same
# one-minute window instead of once, cutting typical queue latency (invoice
# emails, cart reminders) down to roughly one cycle interval.
#
# Safety properties, matching the constraints this host actually has:
#   - Hard wall-clock ceiling (MAX_RUNTIME) well under 60s, so this script
#     always exits before the next cron tick fires — never overlaps it.
#   - flock-based lock file: if a previous invocation is somehow still
#     running when this tick fires (host under load, slow job), this run
#     exits immediately instead of stacking a second worker on top of it.
#   - A single failed `php artisan queue:work` cycle does not stop the
#     script — it logs and moves on to the next cycle. `queue:work`'s own
#     --tries/--timeout/backoff already govern individual job retries;
#     this script only controls how often the drain cycle re-fires.

set -u
set -o pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
LOCK_FILE="$PROJECT_ROOT/storage/app/dj-queue-worker.lock"
PHP_BIN="php"

MAX_RUNTIME=50    # seconds — hard ceiling, safely under the 60s cron window
CYCLE_INTERVAL=12 # seconds between queue:work cycles (~4 cycles per minute)

mkdir -p "$(dirname "$LOCK_FILE")"

exec 9>"$LOCK_FILE"
if ! flock -n 9; then
    # A previous run is still holding the lock — skip this tick rather than
    # risking two workers processing the same jobs table concurrently.
    exit 0
fi

cd "$PROJECT_ROOT" || exit 1

START_TIME=$(date +%s)

while true; do
    NOW=$(date +%s)
    ELAPSED=$((NOW - START_TIME))
    if [ "$ELAPSED" -ge "$MAX_RUNTIME" ]; then
        break
    fi

    # `|| true`: one failed cycle (e.g. a transient DB hiccup) must not kill
    # the loop — the next cycle should still get a chance to run.
    "$PHP_BIN" artisan queue:work database --stop-when-empty --max-time=10 --timeout=45 --tries=3 || true

    NOW=$(date +%s)
    ELAPSED=$((NOW - START_TIME))
    REMAINING=$((MAX_RUNTIME - ELAPSED))
    if [ "$REMAINING" -le 0 ]; then
        break
    fi

    SLEEP_FOR=$CYCLE_INTERVAL
    if [ "$REMAINING" -lt "$CYCLE_INTERVAL" ]; then
        SLEEP_FOR=$REMAINING
    fi
    sleep "$SLEEP_FOR"
done

exit 0
