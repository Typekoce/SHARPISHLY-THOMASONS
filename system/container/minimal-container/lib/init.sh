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
echo "Container Started"
echo "Hostname : $(hostname)"
echo "User     : $(id)"
echo

exec "$@"
EOC
  chmod +x "$ROOTFS/bin/container-start.sh"
}
