#!/usr/bin/env bash
# Central configuration - Single source of truth (DRY)

DEFAULT_NAME="mycontainer"
DEFAULT_ROOTFS_BASE="/tmp/pureshare"

# Base binaries (easy to maintain and extend)
BASE_BINS=(
  bash sh env ls cat echo pwd mkdir rm cp mv ln ps hostname
  mount umount sleep grep awk sed
)

# Python is loaded conditionally
PYTHON_BINS=(python3)
