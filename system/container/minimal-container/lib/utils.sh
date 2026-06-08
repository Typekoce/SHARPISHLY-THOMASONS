#!/usr/bin/env bash
# Shared utilities - DRY principle

log() {
  echo "→ $1"
}

cleanup() {
  set +e
  log "Cleaning up $NAME"
  # Aggressive cleanup of all mounts under rootfs
  mount | awk -v r="$ROOTFS" '$3 ~ r' | cut -d' ' -f3 | sort -r | \
    while read -r m; do
      umount -lf "$m" 2>/dev/null || true
    done
}

make_private_mounts() {
  mount --make-rprivate "$ROOTFS" 2>/dev/null || true
}

require_command() {
  command -v "$1" >/dev/null || { echo "ERROR: Required command '$1' not found"; exit 1; }
}
