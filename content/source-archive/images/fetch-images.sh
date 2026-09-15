#!/usr/bin/env sh
# Downloads the original images from the Google Sites CDN into this folder.
# Run from a normal desktop connection; datacenter IPs get HTTP 403.
# Requires: curl, python3 (for JSON parsing).
set -eu
cd "$(dirname "$0")"
python3 - <<'PY' | while IFS='	' read -r id url; do
  ext=jpg
  printf '%s ... ' "$id"
  curl -fsSL -A "Mozilla/5.0" -o "$id.tmp" "$url" || { echo FAILED; rm -f "$id.tmp"; continue; }
  case "$(head -c 4 "$id.tmp" | od -An -c | tr -d ' ')" in
    *PNG*) ext=png ;;
    *RIFF*) ext=webp ;;
  esac
  mv "$id.tmp" "$id.$ext"; echo "$id.$ext"
done
import json
for i in json.load(open("manifest.json"))["images"]:
    print(f'{i["id"]}\t{i["url"]}')
PY
