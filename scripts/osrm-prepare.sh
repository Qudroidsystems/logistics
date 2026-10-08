#!/usr/bin/env bash
# Prepares road data for the OSRM routing server (one time, then again whenever you want fresher maps).
#
#   bash scripts/osrm-prepare.sh                       # Nigeria (Geofabrik extract, a few hundred MB)
#   OSM_URL=https://download.geofabrik.de/africa/nigeria/kogi-latest.osm.pbf bash scripts/osrm-prepare.sh   # a smaller region if one exists
#
# Then:  sail --profile routing up -d osrm     and set ROUTING_DRIVER=osrm in .env  (OSRM_BASE_URL=http://osrm:5000 inside Sail)
# Needs Docker. It writes to storage/osrm/ (git-ignored data, safe to delete).
set -euo pipefail
cd "$(dirname "$0")/.."

OSM_URL="${OSM_URL:-https://download.geofabrik.de/africa/nigeria-latest.osm.pbf}"
IMAGE="${OSRM_IMAGE:-ghcr.io/project-osrm/osrm-backend:latest}"
DIR="storage/osrm"
mkdir -p "$DIR"

if [ ! -f "$DIR/region.osm.pbf" ] || [ "${REFRESH:-0}" = "1" ]; then
  echo "Downloading $OSM_URL"
  curl -fL --progress-bar -o "$DIR/region.osm.pbf" "$OSM_URL"
fi

run() { docker run --rm -t -v "$PWD/$DIR:/data" "$IMAGE" "$@"; }

echo "Extracting the road network (car profile). This takes a few minutes and a few GB of RAM."
run osrm-extract -p /opt/car.lua /data/region.osm.pbf
run osrm-partition /data/region.osrm
run osrm-customize /data/region.osrm

echo
echo "Done. Start the server:   sail --profile routing up -d osrm"
echo "Check it:                 curl 'http://localhost:5000/route/v1/driving/7.3986,9.0765;7.4986,9.1065?overview=false'"
echo "Then set ROUTING_DRIVER=osrm in .env and run: sail artisan config:clear"
