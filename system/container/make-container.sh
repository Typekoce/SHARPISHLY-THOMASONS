#!/usr/bin/env bash
set -Eeuo pipefail

###############################################################################
# Minimal Namespace Container Runtime
#
# Features:
# - Rootless support
# - Dynamic ELF dependency resolution
# - Python support
# - Network namespace isolation
# - Automatic cleanup
# - Safe mount handling
#
# Usage:
#
#   sudo ./make-container.sh mycontainer
#
#   ./make-container.sh mycontainer /tmp/rootfs /bin/bash
#
#   ./make-container.sh testbox /tmp/testroot python3
#
###############################################################################

NAME="${1:-mycontainer}"
ROOTFS="${2:-/tmp/pureshare-$NAME}"

if [[ $# -ge 2 ]]; then
  shift 2
elif [[ $# -ge 1 ]]; then
  shift 1
fi

if [[ $# -gt 0 ]]; then
  LAUNCH_CMD=("$@")
else
  LAUNCH_CMD=(/bin/bash)
fi

###############################################################################
# Rootless Detection
###############################################################################

if [[ "${EUID}" -ne 0 ]]; then
  if unshare --help 2>/dev/null | grep -q -- '--map-root-user'; then
    ROOTLESS=1
  else
    echo "ERROR: Root privileges or user namespaces required."
    exit 1
  fi
else
  ROOTLESS=0
fi

###############################################################################
# Cleanup
###############################################################################

cleanup() {
  set +e

  for p in proc sys run dev/pts dev/shm tmp; do
    if mountpoint -q "$ROOTFS/$p"; then
      umount -lf "$ROOTFS/$p" 2>/dev/null || true
    fi
  done
}

trap cleanup EXIT

###############################################################################
# Build RootFS
###############################################################################

echo "Building container rootfs:"
echo "  Name   : $NAME"
echo "  RootFS : $ROOTFS"
echo "  Mode   : $([[ "$ROOTLESS" -eq 1 ]] && echo rootless || echo root)"

mkdir -p \
  "$ROOTFS"/{bin,dev,etc,proc,sys,run,tmp,root,home,usr,var,lib,lib64}

chmod 1777 "$ROOTFS/tmp"

###############################################################################
# Copy Binary + Dependencies
###############################################################################

copy_bin() {
  local req="$1"
  local bin

  bin="$(type -P "$req" 2>/dev/null || true)"

  [[ -n "$bin" ]] || return 0
  [[ -f "$bin" ]] || return 0

  echo "Copying binary: $bin"

  local dest="$ROOTFS$bin"

  mkdir -p "$(dirname "$dest")"

  cp -aL --remove-destination "$bin" "$dest"

  ###########################################################################
  # Copy Dynamic Linker
  ###########################################################################

  local interp

  interp="$(
    readelf -l "$bin" 2>/dev/null \
      | awk '/interpreter/ {print $NF}' \
      | tr -d ']'
  )"

  if [[ -n "${interp:-}" && -f "$interp" ]]; then
    local idest="$ROOTFS$interp"

    mkdir -p "$(dirname "$idest")"

    cp -aL --remove-destination "$interp" "$idest"
  fi

  ###########################################################################
  # Copy Shared Libraries
  ###########################################################################

  if ldd "$bin" >/dev/null 2>&1; then
    ldd "$bin" \
      | awk '
          /=> \// { print $3 }
          /^\//   { print $1 }
        ' \
      | while read -r lib; do

          [[ -n "$lib" ]] || continue
          [[ -f "$lib" ]] || continue

          local libdest="$ROOTFS$lib"

          mkdir -p "$(dirname "$libdest")"

          cp -aL --remove-destination "$lib" "$libdest"
        done
  fi
}

###############################################################################
# Python Support
###############################################################################

copy_python_stdlib() {

  command -v python3 >/dev/null 2>&1 || return 0

  echo "Copying Python runtime..."

  local pyver
  pyver="$(python3 -c 'import sys; print(f"{sys.version_info.major}.{sys.version_info.minor}")')"

  local stdlib
  stdlib="$(python3 -c 'import sysconfig; print(sysconfig.get_path("stdlib"))')"

  [[ -d "$stdlib" ]] || return 0

  mkdir -p "$ROOTFS$(dirname "$stdlib")"

  rsync -a \
    --exclude='test' \
    --exclude='tests' \
    --exclude='tkinter' \
    --exclude='idlelib' \
    --exclude='ensurepip' \
    --exclude='distutils' \
    "$stdlib/" \
    "$ROOTFS$stdlib/"
}

###############################################################################
# Base System Files
###############################################################################

ensure_base_files() {

  mkdir -p "$ROOTFS/etc"

  cat > "$ROOTFS/etc/passwd" <<EOF
root:x:0:0:root:/root:/bin/bash
nobody:x:65534:65534:nobody:/nonexistent:/usr/sbin/nologin
EOF

  cat > "$ROOTFS/etc/group" <<EOF
root:x:0:
nogroup:x:65534:
EOF

  echo "$NAME" > "$ROOTFS/etc/hostname"

  cat > "$ROOTFS/etc/hosts" <<EOF
127.0.0.1 localhost
127.0.1.1 $NAME
EOF

  ###########################################################################
  # DNS
  ###########################################################################

  if [[ -f /etc/resolv.conf ]]; then
    cp /etc/resolv.conf "$ROOTFS/etc/resolv.conf"
  else
    cat > "$ROOTFS/etc/resolv.conf" <<EOF
nameserver 1.1.1.1
nameserver 8.8.8.8
EOF
  fi
}

###############################################################################
# Device Nodes
###############################################################################

ensure_dev_nodes() {

  mkdir -p "$ROOTFS/dev"

  ###########################################################################
  # Rootless fallback
  ###########################################################################

  if [[ "$ROOTLESS" -eq 1 ]]; then
    echo "Skipping mknod in rootless mode."
    return 0
  fi

  local name mode type major minor

  while read -r name mode type major minor; do

    [[ -e "$ROOTFS/dev/$name" ]] && continue

    mknod \
      -m "$mode" \
      "$ROOTFS/dev/$name" \
      "$type" \
      "$major" \
      "$minor"

  done <<'EOF'
null 666 c 1 3
zero 666 c 1 5
random 444 c 1 8
urandom 444 c 1 9
tty 666 c 5 0
EOF
}

###############################################################################
# Copy Userland
###############################################################################

copy_userland() {

  local bins=(
    bash
    sh
    env
    ls
    cat
    echo
    pwd
    mkdir
    rm
    cp
    mv
    ln
    ps
    hostname
    mount
    umount
    sleep
    grep
    awk
    sed
    python3
  )

  local b

  for b in "${bins[@]}"; do
    copy_bin "$b"
  done

  copy_python_stdlib
}

###############################################################################
# Bootstrap Script
###############################################################################

write_init_script() {

  cat > "$ROOTFS/bin/container-start.sh" <<'EOF'
#!/bin/bash
set -eu

mkdir -p \
  /proc \
  /sys \
  /run \
  /tmp \
  /dev/pts \
  /dev/shm

mount -t proc proc /proc 2>/dev/null || true
mount -t sysfs sys /sys 2>/dev/null || true
mount -t tmpfs tmpfs /run 2>/dev/null || true
mount -t tmpfs tmpfs /tmp 2>/dev/null || true

mount -t devpts \
  -o newinstance,ptmxmode=0666 \
  devpts \
  /dev/pts 2>/dev/null || true

ln -snf pts/ptmx /dev/ptmx 2>/dev/null || true

hostname "$(cat /etc/hostname)" 2>/dev/null || true

echo
echo "Container Started"
echo "Hostname: $(hostname)"
echo "User    : $(id)"
echo

exec "$@"
EOF

  chmod +x "$ROOTFS/bin/container-start.sh"
}

###############################################################################
# Build Container
###############################################################################

copy_userland
ensure_base_files
ensure_dev_nodes
write_init_script

###############################################################################
# Launch
###############################################################################

echo
echo "Launching container..."
echo

COMMON_FLAGS=(
  --mount
  --uts
  --ipc
  --pid
  --fork
  --mount-proc
)

###############################################################################
# Rootless
###############################################################################

if [[ "$ROOTLESS" -eq 1 ]]; then

  exec unshare \
    --user \
    --map-root-user \
    --net \
    "${COMMON_FLAGS[@]}" \
    chroot "$ROOTFS" \
    /bin/bash \
    /bin/container-start.sh \
    "${LAUNCH_CMD[@]}"

###############################################################################
# Root
###############################################################################

else

  exec unshare \
    --net \
    "${COMMON_FLAGS[@]}" \
    chroot "$ROOTFS" \
    /bin/bash \
    /bin/container-start.sh \
    "${LAUNCH_CMD[@]}"
fi
