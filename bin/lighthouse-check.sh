#!/usr/bin/env bash
set -euo pipefail

if [ $# -lt 1 ]; then
    echo "Usage: $0 <url> [label] [preset=desktop|mobile]"
    exit 1
fi

URL="$1"
LABEL="${2:-baseline}"
PRESET="${3:-desktop}"

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
OUT_DIR="storage/app/lighthouse"
mkdir -p "$OUT_DIR"

OUT_FILE="${OUT_DIR}/${LABEL}-${PRESET}-${TIMESTAMP}.json"

echo "Running Lighthouse on ${URL} (${PRESET})..."
npx -y lighthouse "$URL" \
    --output json \
    --output-path "$OUT_FILE" \
    --preset "$PRESET" \
    --quiet \
    --chrome-flags="--headless --no-sandbox"

echo "Saved: $OUT_FILE"
