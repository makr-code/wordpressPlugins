#!/usr/bin/env bash
# deploy-to-themisdb-org.sh
# ─────────────────────────────────────────────────────────────────────────────
# Manual upload of WP plugin ZIPs to releases.themisdb.org via rsync over SSH.
# Use this when ThemisDB CI is unavailable or for hot-fix uploads.
#
# Usage:
#   ./scripts/deploy-to-themisdb-org.sh [VERSION]
#
# Options:
#   VERSION   Tag to upload, e.g. 1.2.0 (without 'v' prefix)
#             Defaults to the latest git tag.
#
# Required environment variables (or set via .env.deploy):
#   THEMISDB_ORG_HOST   Server hostname / IP (e.g. releases.themisdb.org)
#   THEMISDB_ORG_USER   SSH login user
#   THEMISDB_ORG_KEY    Path to SSH private key (~/.ssh/id_themisdb)
#   THEMISDB_ORG_PATH   Remote base path  (e.g. /var/www/releases.themisdb.org/public)
#
# Layout on the server after upload:
#   $THEMISDB_ORG_PATH/v1.2.0/
#       themisdb-order-request-1.2.0.zip
#       themisdb-order-request-1.2.0.zip.sha256
#       themisdb-support-portal-1.2.0.zip
#       themisdb-support-portal-1.2.0.zip.sha256
#   $THEMISDB_ORG_PATH/latest/          ← symlinked or overwritten copy
#       themisdb-order-request.zip
#       themisdb-support-portal.zip
#   $THEMISDB_ORG_PATH/update-manifest.json
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

# ── load optional .env.deploy ────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

if [[ -f "${REPO_ROOT}/.env.deploy" ]]; then
  # shellcheck disable=SC1091
  source "${REPO_ROOT}/.env.deploy"
fi

# ── required vars ─────────────────────────────────────────────────────────────
: "${THEMISDB_ORG_HOST:?'THEMISDB_ORG_HOST not set'}"
: "${THEMISDB_ORG_USER:?'THEMISDB_ORG_USER not set'}"
: "${THEMISDB_ORG_KEY:?'THEMISDB_ORG_KEY not set'}"
: "${THEMISDB_ORG_PATH:?'THEMISDB_ORG_PATH not set'}"

# ── version ───────────────────────────────────────────────────────────────────
VERSION="${1:-}"
if [[ -z "$VERSION" ]]; then
  VERSION="$(git -C "${REPO_ROOT}" describe --tags --abbrev=0 2>/dev/null | sed 's/^v//')"
fi
if [[ -z "$VERSION" ]]; then
  echo "[ERROR] Could not determine version. Pass it as first argument." >&2
  exit 1
fi
TAG="v${VERSION}"
echo "[INFO]  Deploying version ${TAG} to ${THEMISDB_ORG_HOST}"

# ── build dir ─────────────────────────────────────────────────────────────────
BUILD_DIR="${REPO_ROOT}/build"
if [[ ! -d "${BUILD_DIR}" ]]; then
  echo "[ERROR] Build directory '${BUILD_DIR}' not found. Run 'scripts/build-plugins.sh' first." >&2
  exit 1
fi

ZIPS=("${BUILD_DIR}"/*.zip)
if [[ ${#ZIPS[@]} -eq 0 || ! -f "${ZIPS[0]}" ]]; then
  echo "[ERROR] No .zip files found in ${BUILD_DIR}." >&2
  exit 1
fi

# ── SSH options ───────────────────────────────────────────────────────────────
SSH_OPTS="-i ${THEMISDB_ORG_KEY} -o StrictHostKeyChecking=accept-new -o BatchMode=yes"
REMOTE="${THEMISDB_ORG_USER}@${THEMISDB_ORG_HOST}"

# ── ensure remote dirs exist ─────────────────────────────────────────────────
echo "[INFO]  Creating remote directories ..."
# shellcheck disable=SC2029
ssh ${SSH_OPTS} "${REMOTE}" "
  set -e
  mkdir -p '${THEMISDB_ORG_PATH}/${TAG}'
  mkdir -p '${THEMISDB_ORG_PATH}/latest'
"

# ── upload versioned artifacts ────────────────────────────────────────────────
echo "[INFO]  Uploading ${TAG} artifacts ..."
rsync -avz --progress \
  -e "ssh ${SSH_OPTS}" \
  "${BUILD_DIR}/"*.zip \
  "${BUILD_DIR}/"*.sha256 \
  "${REMOTE}:${THEMISDB_ORG_PATH}/${TAG}/"

# ── generate update-manifest.json locally and upload ─────────────────────────
echo "[INFO]  Generating update-manifest.json ..."
MANIFEST_TMP="$(mktemp /tmp/update-manifest.XXXXXX.json)"

# Build plugin entries from ZIP files
PLUGIN_ENTRIES=""
for zip_file in "${BUILD_DIR}"/*.zip; do
  filename="$(basename "${zip_file}")"
  # strip version suffix: themisdb-order-request-1.2.0.zip → themisdb-order-request
  slug="${filename%-${VERSION}.zip}"
  sha_file="${zip_file}.sha256"
  sha256=""
  if [[ -f "${sha_file}" ]]; then
    sha256="$(awk '{print $1}' "${sha_file}")"
  fi

  # Read fields from update-info.json if available
  INFO_FILE="${REPO_ROOT}/${slug}/update-info.json"
  name="${slug}"
  requires="5.0"
  tested="6.4"
  requires_php="7.4"
  description=""
  if [[ -f "${INFO_FILE}" ]]; then
    name="$(python3 -c "import json,sys; d=json.load(open('${INFO_FILE}')); print(d.get('name','${slug}'))" 2>/dev/null || echo "${slug}")"
    requires="$(python3 -c "import json,sys; d=json.load(open('${INFO_FILE}')); print(d.get('requires','5.0'))" 2>/dev/null || echo "5.0")"
    tested="$(python3 -c "import json,sys; d=json.load(open('${INFO_FILE}')); print(d.get('tested','6.4'))" 2>/dev/null || echo "6.4")"
    requires_php="$(python3 -c "import json,sys; d=json.load(open('${INFO_FILE}')); print(d.get('requires_php','7.4'))" 2>/dev/null || echo "7.4")"
    description="$(python3 -c "import json,sys; d=json.load(open('${INFO_FILE}')); print(d.get('description',''))" 2>/dev/null || echo "")"
  fi

  comma=","
  PLUGIN_ENTRIES="${PLUGIN_ENTRIES}${comma}
    \"${slug}\": {
      \"version\": \"${VERSION}\",
      \"name\": \"${name}\",
      \"requires\": \"${requires}\",
      \"tested\": \"${tested}\",
      \"requires_php\": \"${requires_php}\",
      \"description\": \"${description}\",
      \"download_url\": \"https://releases.themisdb.org/${TAG}/${filename}\",
      \"sha256\": \"${sha256}\",
      \"changelog_url\": \"https://github.com/makr-code/ThemisDB/releases/tag/${TAG}\"
    }"
done

# Remove leading comma from first entry
PLUGIN_ENTRIES="${PLUGIN_ENTRIES#,}"

cat > "${MANIFEST_TMP}" <<EOF
{
  "manifest_version": 1,
  "release_date": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "latest_version": "${VERSION}",
  "plugins": {${PLUGIN_ENTRIES}
  }
}
EOF

echo "[INFO]  Uploading update-manifest.json ..."
rsync -avz -e "ssh ${SSH_OPTS}" \
  "${MANIFEST_TMP}" \
  "${REMOTE}:${THEMISDB_ORG_PATH}/update-manifest.json"

# ── update /latest symlinks ───────────────────────────────────────────────────
echo "[INFO]  Updating /latest/ directory ..."
for zip_file in "${BUILD_DIR}"/*.zip; do
  filename="$(basename "${zip_file}")"
  slug="${filename%-${VERSION}.zip}"
  ssh ${SSH_OPTS} "${REMOTE}" \
    "cp '${THEMISDB_ORG_PATH}/${TAG}/${filename}' '${THEMISDB_ORG_PATH}/latest/${slug}.zip'"
done

rm -f "${MANIFEST_TMP}"

echo ""
echo "[DONE]  Version ${TAG} deployed to https://releases.themisdb.org/"
echo "        Update manifest: https://releases.themisdb.org/update-manifest.json"
