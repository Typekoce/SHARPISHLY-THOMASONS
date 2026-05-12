#!/usr/bin/env bash
# Model: Dependency resolution and copying

copy_binary() {
  local req="$1"
  local bin
  bin="$(type -P "$req" 2>/dev/null || true)"
  [[ -n "$bin" && -f "$bin" ]] || return 0

  log "Copying binary: $req"

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
    ldd "$bin" | awk '/=> \/\/ {print $3} /^\// {print $1}' | \
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
