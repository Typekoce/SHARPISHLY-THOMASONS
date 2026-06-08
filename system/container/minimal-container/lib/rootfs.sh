#!/usr/bin/env bash
# Model: Root filesystem construction

build_rootfs() {
  log "Building rootfs at: $ROOTFS"

  mkdir -p "$ROOTFS"/{bin,dev,etc,proc,sys,run,tmp,root,home,usr,var,lib,lib64}
  chmod 1777 "$ROOTFS/tmp"

  create_base_files
  create_dev_nodes
  copy_userland
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
