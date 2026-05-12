#!/usr/bin/env bash
# =============================================================================
# Minimal Namespace Container Runtime - Project Generator (FIXED v3)
# Creates full MVC-style project structure - PATHS FIXED
# =============================================================================

set -Eeuo pipefail

PROJECT_DIR="${1:-minimal-container}"

echo "Generating Minimal Container Runtime project in: $PROJECT_DIR"
echo "==================================================="

# Create directory structure
mkdir -p "$PROJECT_DIR"/{config,lib,containers}

# ====================== config/defaults.sh ======================
cat > "$PROJECT_DIR/config/defaults.sh" <<'EOF_CONFIG'
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
EOF_CONFIG

# ====================== lib/utils.sh ======================
cat > "$PROJECT_DIR/lib/utils.sh" <<'EOF_UTILS'
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
EOF_UTILS

# ====================== lib/dependencies.sh ======================
cat > "$PROJECT_DIR/lib/dependencies.sh" <<'EOF_DEPS'
#!/usr/bin/env bash
# Model: Dependency resolution and copying

copy_binary() {
  local req="$1"
  local bin
  bin="$(type -P "$req" 2>/dev/null || true)"
  [[ -n "$bin" && -f "$bin" ]] || return 0

  log "Copying binary: $req -> $bin"

  local dest="$ROOTFS$bin"
  mkdir -p "$(dirname "$dest")"
  cp -aL --remove-destination "$bin" "$dest"

  # Copy dynamic linker (ld-linux)
  local interp
  interp="$(readelf -l "$bin" 2>/dev/null | awk '/interpreter/ {print $NF}' | tr -d ']')"
  if [[ -n "${interp:-}" && -f "$interp" ]]; then
    local idest="$ROOTFS$interp"
    mkdir -p "$(dirname "$idest")"
    cp -aL --remove-destination "$interp" "$idest"
  fi

  # Copy shared libraries
  if ldd "$bin" >/dev/null 2>&1; then
    ldd "$bin" | awk '/=> \/\/ {print $3} /^\// {print $1}' | sort -u | \
    while read -r lib; do
      [[ -f "$lib" ]] || continue
      local libdest="$ROOTFS$lib"
      mkdir -p "$(dirname "$libdest")"
      cp -aL --remove-destination "$lib" "$libdest"
    done
  fi
}

copy_python_if_needed() {
  [[ " ${LAUNCH_CMD[*]} " == *"python"* || "$NAME" == *py* ]] || return 0
  log "Copying minimal Python runtime"

  local stdlib
  stdlib="$(python3 -c 'import sysconfig; print(sysconfig.get_path("stdlib"))' 2>/dev/null || true)"
  [[ -d "$stdlib" ]] || return 0

  mkdir -p "$ROOTFS$(dirname "$stdlib")"
  rsync -a --exclude='test' --exclude='tests' --exclude='__pycache__' \
        --exclude='tkinter' --exclude='idlelib' --exclude='ensurepip' \
        "$stdlib/" "$ROOTFS$stdlib/" 2>/dev/null || true
}
EOF_DEPS

# ====================== lib/rootfs.sh (SYMLINKS ADDED) ======================
cat > "$PROJECT_DIR/lib/rootfs.sh" <<'EOF_ROOTFS'
#!/usr/bin/env bash
# Model: Root filesystem construction

build_rootfs() {
  log "Building rootfs at: $ROOTFS"

  mkdir -p "$ROOTFS"/{bin,dev,etc,proc,sys,run,tmp,root,home,usr,var,lib,lib64}
  chmod 1777 "$ROOTFS/tmp"

  create_base_files
  create_dev_nodes
  copy_userland
  create_bin_symlinks
  write_init_script
}

create_base_files() {
  mkdir -p "$ROOTFS/etc"

  cat > "$ROOTFS/etc/passwd" <<'EOP'
root:x:0:0:root:/root:/bin/bash
nobody:x:65534:65534:nobody:/nonexistent:/usr/sbin/nologin
EOP

  cat > "$ROOTFS/etc/group" <<'EOG'
root:x:0:
nogroup:x:65534:
EOG

  echo "$NAME" > "$ROOTFS/etc/hostname"

  cat > "$ROOTFS/etc/hosts" <<EOF_HOSTS
127.0.0.1 localhost
::1       localhost ip6-localhost
127.0.1.1 $NAME
EOF_HOSTS

  cp -L /etc/resolv.conf "$ROOTFS/etc/resolv.conf" 2>/dev/null || true
}

create_dev_nodes() {
  mkdir -p "$ROOTFS/dev"
  [[ "$ROOTLESS" -eq 1 ]] && return 0

  while read -r name mode type major minor; do
    [[ -e "$ROOTFS/dev/$name" ]] && continue
    mknod -m "$mode" "$ROOTFS/dev/$name" "$type" "$major" "$minor" 2>/dev/null || true
  done <<'EOF_DEV'
null 666 c 1 3
zero 666 c 1 5
random 444 c 1 8
urandom 444 c 1 9
tty 666 c 5 0
EOF_DEV
}

copy_userland() {
  local b
  for b in "${BASE_BINS[@]}"; do
    copy_binary "$b"
  done

  # Lateral minimalism: Use BusyBox when available
  if command -v busybox >/dev/null; then
    log "Integrating BusyBox"
    cp "$(command -v busybox)" "$ROOTFS/bin/"
    chroot "$ROOTFS" /bin/busybox --install -s /bin 2>/dev/null || true
  fi

  copy_python_if_needed
}

create_bin_symlinks() {
  log "Creating standard /bin symlinks (FHS compliance)"
  mkdir -p "$ROOTFS/bin"
  
  local bins=(bash sh env ls cat echo pwd mkdir rm cp mv ln ps hostname mount umount sleep grep awk sed)
  for bin in "${bins[@]}"; do
    # Check usr/bin first (Ubuntu/Debian), then bin
    if [[ -f "$ROOTFS/usr/bin/$bin" ]]; then
      ln -sf "usr/bin/$bin" "$ROOTFS/bin/$bin"
    elif [[ -f "$ROOTFS/bin/$bin" ]]; then
      : # Already exists
    else
      log "WARNING: $bin not found in rootfs"
    fi
  done
}
EOF_ROOTFS

# ====================== lib/init.sh ======================
cat > "$PROJECT_DIR/lib/init.sh" <<'EOF_INIT'
#!/usr/bin/env bash
# Container initialization script generator

write_init_script() {
  cat > "$ROOTFS/bin/container-start.sh" <<'EOC'
#!/bin/bash
set -eu

mkdir -p /proc /sys /run /tmp /dev/pts /dev/shm

mount -t proc proc /proc 2>/dev/null || true
mount -t sysfs sys /sys 2>/dev/null || true
mount -t tmpfs tmpfs /run 2>/dev/null || true
mount -t tmpfs tmpfs /tmp 2>/dev/null || true

mount -t devpts -o newinstance,ptmxmode=0666 devpts /dev/pts 2>/dev/null || true
ln -snf pts/ptmx /dev/ptmx 2>/dev/null || true

hostname "$(cat /etc/hostname)" 2>/dev/null || true

echo
echo "Container Started ✓"
echo "Hostname : $(hostname)"
echo "User     : $(id)"
echo "PATH     : $PATH"
ls -la /bin/bash /usr/bin/bash 2>/dev/null || echo "/bin/bash not found"
echo

exec "$@"
EOC
  chmod +x "$ROOTFS/bin/container-start.sh"
}
EOF_INIT

# ====================== make-container.sh ======================
cat > "$PROJECT_DIR/make-container.sh" <<'EOF_MAIN'
#!/usr/bin/env bash
# Controller / Orchestrator - Thin layer

set -Eeuo pipefail

source config/defaults.sh
source lib/utils.sh
source lib/dependencies.sh
source lib/rootfs.sh
source lib/init.sh

echo "Building container rootfs:"
echo "  Name   : $1"
echo "  RootFS : $2"
echo "  Mode   : $( [[ $EUID -ne 0 ]] && echo rootless || echo root )"

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

# Rootless detection
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
  exec unshare --user --map-root-user "${COMMON_FLAGS[@]}" \
    chroot "$ROOTFS" /bin/bash /bin/container-start.sh "${LAUNCH_CMD[@]}"
else
  exec unshare --net "${COMMON_FLAGS[@]}" \
    chroot "$ROOTFS" /bin/bash /bin/container-start.sh "${LAUNCH_CMD[@]}"
fi
EOF_MAIN

# Make scripts executable
chmod +x "$PROJECT_DIR/make-container.sh"
chmod +x "$PROJECT_DIR"/lib/*.sh 2>/dev/null || true

echo
echo "✅ Project generated successfully! (PATHS FIXED)"
echo
echo "Next steps:"
echo "   cd $PROJECT_DIR"
echo "   sudo ./make-container.sh demo-1"
echo "   sudo ./make-container.sh pybox python3"
echo "   sudo ./make-container.sh nettest nc -l 8080  # rootful networking test"
echo
echo "🎉 Now works on Ubuntu/Debian/Alpine - /bin symlinks auto-created"
echo "Done! Project created in ./${PROJECT_DIR}/"
