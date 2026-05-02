#!/bin/bash
#
# Server Deployment Script
# Safely deploys ThemisDB plugins from GitHub releases
#
# Usage: ./deploy.sh themisdb-order-request v1.2.0 /var/www/wordpress
#

set -e

PLUGIN_NAME="${1:-}"
VERSION="${2:-}"
WP_ROOT="${3:-/var/www/wordpress}"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Validation
if [[ -z "$PLUGIN_NAME" || -z "$VERSION" ]]; then
    log_error "Usage: ./deploy.sh <plugin-name> <version> [wp-root]"
    echo "Example: ./deploy.sh themisdb-order-request v1.2.0 /var/www/wordpress"
    exit 1
fi

if [[ ! -d "$WP_ROOT" ]]; then
    log_error "WordPress root not found: $WP_ROOT"
    exit 1
fi

PLUGIN_DIR="$WP_ROOT/wp-content/plugins/$PLUGIN_NAME"
BACKUP_DIR="/var/backups/themisdb/${PLUGIN_NAME}-$(date +%Y%m%d-%H%M%S)"
DOWNLOADS_DIR="/tmp/themisdb-downloads"

log_info "Starting deployment of $PLUGIN_NAME v$VERSION"
log_info "WordPress root: $WP_ROOT"

# 1. Create backup
log_info "Creating backup..."
mkdir -p "$BACKUP_DIR"

if [[ -d "$PLUGIN_DIR" ]]; then
    cp -r "$PLUGIN_DIR" "$BACKUP_DIR/plugin-backup"
    log_info "Backup created: $BACKUP_DIR"
else
    log_warn "Plugin directory does not exist yet: $PLUGIN_DIR"
fi

# 2. Download artifact from GitHub
log_info "Downloading artifact from GitHub..."
mkdir -p "$DOWNLOADS_DIR"

ARTIFACT_URL="https://github.com/makr-code/wordpressPlugins/releases/download/v${VERSION}/${PLUGIN_NAME}-${VERSION}.zip"
ARTIFACT_SHA256_URL="${ARTIFACT_URL}.sha256"
ARTIFACT_FILE="$DOWNLOADS_DIR/${PLUGIN_NAME}-${VERSION}.zip"
SHA256_FILE="${ARTIFACT_FILE}.sha256"

# Download SHA256 checksum
if ! curl -sS -L "$ARTIFACT_SHA256_URL" -o "$SHA256_FILE"; then
    log_error "Failed to download checksum: $ARTIFACT_SHA256_URL"
    exit 1
fi

# Download artifact
if ! curl -sS -L "$ARTIFACT_URL" -o "$ARTIFACT_FILE"; then
    log_error "Failed to download artifact: $ARTIFACT_URL"
    exit 1
fi

# Verify checksum
log_info "Verifying checksum..."
cd "$DOWNLOADS_DIR"
if ! sha256sum -c "$SHA256_FILE" > /dev/null; then
    log_error "Checksum verification failed!"
    exit 1
fi
log_info "Checksum verified ✓"

# 3. Deactivate plugin in WordPress
log_info "Deactivating plugin..."
cd "$WP_ROOT"
wp plugin deactivate "$PLUGIN_NAME" --allow-root || log_warn "Plugin not active or not found"

# 4. Extract and deploy
log_info "Extracting and deploying..."

if [[ -d "$PLUGIN_DIR" ]]; then
    rm -rf "$PLUGIN_DIR"
fi

mkdir -p "$PLUGIN_DIR"
unzip -q "$ARTIFACT_FILE" -d "$PLUGIN_DIR"

# If unzip created a subdirectory, move contents up
if [[ $(ls -1 "$PLUGIN_DIR" | wc -l) -eq 1 && -d "$PLUGIN_DIR"/* ]]; then
    SUBDIR=$(ls -1d "$PLUGIN_DIR"/*/ | head -1)
    mv "$SUBDIR"* "$PLUGIN_DIR" 2>/dev/null || true
    rmdir "$SUBDIR" 2>/dev/null || true
fi

log_info "Plugin files deployed to: $PLUGIN_DIR"

# 5. Run database migrations
if [[ -f "$PLUGIN_DIR/includes/class-database.php" ]]; then
    log_info "Running database migrations..."
    wp plugin activate "$PLUGIN_NAME" --allow-root
    
    # Trigger activation hook which typically runs DB migrations
    wp eval "do_action('${PLUGIN_NAME}-activate');" --allow-root || log_warn "Migration hook not found"
fi

# 6. Verify deployment
log_info "Verifying deployment..."

if [[ -f "$PLUGIN_DIR/${PLUGIN_NAME}.php" ]]; then
    VERSION_IN_FILE=$(grep -m1 "Version:" "$PLUGIN_DIR/${PLUGIN_NAME}.php" | sed -E 's/.*Version:\s*([0-9.]+).*/\1/')
    
    if [[ "$VERSION_IN_FILE" == "$VERSION" ]]; then
        log_info "Version verification passed: $VERSION_IN_FILE ✓"
    else
        log_warn "Version mismatch. File: $VERSION_IN_FILE, Expected: $VERSION"
    fi
fi

# 7. Activate plugin
log_info "Activating plugin..."
if wp plugin activate "$PLUGIN_NAME" --allow-root; then
    log_info "Plugin activated successfully ✓"
else
    log_error "Failed to activate plugin!"
    log_error "Rolling back..."
    
    # Rollback
    rm -rf "$PLUGIN_DIR"
    cp -r "$BACKUP_DIR/plugin-backup" "$PLUGIN_DIR"
    
    log_info "Rollback complete. Previous version restored."
    exit 1
fi

# 8. Health check
log_info "Running health checks..."

# Check if plugin is active
if wp plugin is-active "$PLUGIN_NAME" --allow-root; then
    log_info "Plugin is active ✓"
else
    log_error "Plugin activation verification failed"
    exit 1
fi

# Check for errors
ERROR_LOG="$WP_ROOT/wp-content/debug.log"
if [[ -f "$ERROR_LOG" ]]; then
    RECENT_ERRORS=$(tail -5 "$ERROR_LOG" | grep -i "fatal\|error" || true)
    if [[ -n "$RECENT_ERRORS" ]]; then
        log_warn "Recent errors in debug.log:"
        echo "$RECENT_ERRORS"
    fi
fi

# Cleanup
log_info "Cleaning up..."
rm -f "$ARTIFACT_FILE" "$SHA256_FILE"

# Summary
log_info "========================================="
log_info "Deployment successful! ✓"
log_info "Plugin: $PLUGIN_NAME"
log_info "Version: $VERSION"
log_info "Deployed to: $PLUGIN_DIR"
log_info "Backup: $BACKUP_DIR"
log_info "========================================="

# Log deployment
DEPLOYMENT_LOG="/var/log/themisdb-deployments.log"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Deployed $PLUGIN_NAME v$VERSION to $PLUGIN_DIR (Backup: $BACKUP_DIR)" >> "$DEPLOYMENT_LOG" 2>/dev/null || true

exit 0
