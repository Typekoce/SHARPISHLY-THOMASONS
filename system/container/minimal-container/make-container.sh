#!/usr/bin/env bash
# Controller / Orchestrator - Thin layer

set -Eeuo pipefail

source config/defaults.sh
source lib/utils.sh
source lib/dependencies.sh
source lib/rootfs.sh
source lib/init.sh

# ROBUST Argument parsing
NAME="${1:-$DEFAULT_NAME}"
ROOTFS="${2:-$DEFAULT_ROOTFS_BASE-$NAME}"

# Parse remaining args as LAUNCH_CMD
shift 2
if [[ $# -eq 0 ]]; then
  LAUNCH_CMD=(/bin/bash)
else
  LAUNCH_CMD=("$@")
fi

# Rootless detection (safer)
if [[ $EUID -ne 0 ]]; then
  ROOTLESS=1
  if ! unshare --user --map-root-user true 2>/dev/null; then
    echo "ERROR: User namespaces required for rootless mode (kernel.unprivileged_userns_clone=1)"
    exit 1
  fi
else
  ROOTLESS=0
fi

trap cleanup EXIT

# Main execution flow
build_rootfs
make_private_mounts

echo
log "Launching container '$NAME'..."

COMMON_FLAGS=(--mount --uts --ipc --pid --fork --mount-proc)

if [[ "$ROOTLESS" -eq 1 ]]; then
  # Rootless: no net namespace, user namespace only
  exec unshare --user --map-root-user "${COMMON_FLAGS[@]}" \
    chroot "$ROOTFS" /bin/bash /bin/container-start.sh "${LAUNCH_CMD[@]}"
else
  # Rootful: full isolation including net
  exec unshare --net "${COMMON_FLAGS[@]}" \
    chroot "$ROOTFS" /bin/bash /bin/container-start.sh "${LAUNCH_CMD[@]}"
fi
