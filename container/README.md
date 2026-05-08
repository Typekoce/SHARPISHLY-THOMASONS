# Minimal Namespace Container Runtime

A lightweight Linux container runtime written entirely in Bash using:

- Linux namespaces
- `unshare`
- `chroot`
- isolated mount namespaces
- dynamic ELF dependency copying

This project demonstrates the foundational mechanics behind:

- Docker
- LXC
- Bubblewrap
- systemd-nspawn
- OCI runtimes

without requiring Docker, Podman, Kubernetes, or external container frameworks.

---

# Features

## Isolation

- UTS namespace isolation
- PID namespace isolation
- IPC namespace isolation
- Mount namespace isolation
- Optional network namespace isolation
- Rootless container support

---

## Runtime Features

- Dynamic ELF dependency resolution
- Automatic shared library copying
- Dynamic linker detection
- Python runtime support
- Minimal `/dev` creation
- Automatic cleanup
- Temporary isolated `/tmp`
- DNS support
- Interactive shell support

---

# How It Works

The script:

1. Creates a minimal root filesystem
2. Copies selected binaries
3. Resolves and copies shared libraries
4. Copies the ELF interpreter (`ld-linux`)
5. Creates basic `/etc` files
6. Enters isolated namespaces using `unshare`
7. Performs a `chroot`
8. Starts the container init script

---

# Requirements

## Linux Kernel Features

Your system must support:

- user namespaces
- mount namespaces
- PID namespaces
- `unshare`
- `chroot`

---

## Required Packages

Ubuntu/Debian:

```bash
sudo apt install \
  bash \
  coreutils \
  util-linux \
  binutils \
  rsync
```

Optional:

```bash
sudo apt install busybox-static
```

---

# Usage

## Basic Container

```bash
sudo ./make-container.sh demo
```

---

## Rootless Container

```bash
./make-container.sh demo
```

Requires:

```bash
unshare --map-root-user
```

enabled by the kernel.

---

## Custom RootFS Location

```bash
./make-container.sh demo /tmp/demo-rootfs
```

---

## Run Python

```bash
./make-container.sh pybox /tmp/pyroot python3
```

---

## Run Custom Command

```bash
./make-container.sh demo /tmp/rootfs /bin/bash
```

---

# Example Output

```text
Launching container...

Container Started
Hostname: demo
User    : uid=0(root) gid=0(root) groups=0(root)

bash-5.2#
```

---

# Verify Isolation

## Check Hostname Isolation

Inside container:

```bash
hostname
```

Host machine:

```bash
hostname
```

They should differ.

---

## Check PID Namespace

Inside container:

```bash
ps
```

Only a few processes should appear.

---

## Check Filesystem Isolation

Inside container:

```bash
touch /tmp/container-test
```

On host:

```bash
ls /tmp/container-test
```

The file should not exist on the host.

---

# Project Structure

```text
rootfs/
├── bin/
├── dev/
├── etc/
├── home/
├── lib/
├── lib64/
├── proc/
├── root/
├── run/
├── sys/
├── tmp/
├── usr/
└── var/
```

---

# Key Linux Concepts

## Namespaces

Namespaces isolate global system resources.

This project uses:

| Namespace | Purpose |
|---|---|
| UTS | Hostname isolation |
| PID | Process isolation |
| IPC | Shared memory/message queue isolation |
| Mount | Filesystem isolation |
| Network | Network stack isolation |

---

## chroot

`chroot` changes the apparent root directory:

```bash
chroot /new/root /bin/bash
```

Processes cannot access files outside the new root.

---

## Dynamic Linking

Linux executables depend on:

- shared libraries
- dynamic ELF interpreter

Example:

```text
/lib64/ld-linux-x86-64.so.2
```

The runtime automatically copies required dependencies using:

```bash
ldd
readelf
```

---

# Security Warning

This is an educational runtime.

It is NOT production secure.

Missing protections include:

- seccomp
- AppArmor
- SELinux
- capability filtering
- cgroups
- readonly rootfs
- syscall filtering
- pivot_root
- user remapping isolation
- hardened networking

Do NOT run untrusted workloads.

---

# Current Limitations

| Feature | Status |
|---|---|
| Rootless Mode | Partial |
| Networking | Minimal |
| OverlayFS | Not implemented |
| Cgroups | Not implemented |
| OCI Images | Not implemented |
| Security Hardening | Minimal |
| Container Registry | Not implemented |

---

# Recommended Improvements

---

## 1. BusyBox Integration

Replace most copied binaries with:

```bash
busybox
```

Benefits:

- smaller rootfs
- fewer dependencies
- faster startup

---

## 2. Networking

Add:

- veth pairs
- bridges
- NAT
- DHCP

---

## 3. Overlay Filesystems

Implement writable layers similar to Docker.

---

## 4. Cgroups v2

Add:

- memory limits
- CPU limits
- PID limits

Protects host from runaway containers.

---

## 5. Seccomp

Block dangerous syscalls.

Example:

- `ptrace`
- `mount`
- `reboot`

---

## 6. pivot_root

More secure than `chroot`.

Used by modern container runtimes.

---

## 7. Capability Dropping

Reduce privileges for container root.

---

# Comparison to Real Container Runtimes

| Runtime | Similar Concepts |
|---|---|
| Docker | namespaces + cgroups + OCI |
| LXC | lightweight Linux containers |
| Bubblewrap | rootless sandboxing |
| systemd-nspawn | namespace containerization |
| runc | OCI runtime implementation |

---

# Learning Resources

## Container Runtimes

- [runc](https://github.com/opencontainers/runc?utm_source=chatgpt.com)
- [bubblewrap](https://github.com/containers/bubblewrap?utm_source=chatgpt.com)
- [nsjail](https://github.com/google/nsjail?utm_source=chatgpt.com)
- [LXC](https://linuxcontainers.org/lxc/?utm_source=chatgpt.com)

---

# Useful Commands

## Inspect Namespaces

```bash
lsns
```

---

## Inspect Process Namespaces

```bash
readlink /proc/<pid>/ns/*
```

---

## View Mounts

```bash
mount
```

---

## View ELF Dependencies

```bash
ldd /bin/bash
```

---

## View ELF Interpreter

```bash
readelf -l /bin/bash
```

---

# Architecture Overview

```text
+---------------------------+
| Host Linux Kernel         |
+---------------------------+
             |
             v
+---------------------------+
| unshare() Namespaces      |
+---------------------------+
             |
             v
+---------------------------+
| Mount Namespace           |
| PID Namespace             |
| UTS Namespace             |
| IPC Namespace             |
| Network Namespace         |
+---------------------------+
             |
             v
+---------------------------+
| chroot() RootFS           |
+---------------------------+
             |
             v
+---------------------------+
| container-start.sh        |
+---------------------------+
             |
             v
+---------------------------+
| Isolated Shell            |
+---------------------------+
```

---

# Educational Purpose

This project is ideal for learning:

- Linux internals
- namespaces
- container runtimes
- ELF binaries
- dynamic linking
- root filesystems
- process isolation
- lightweight virtualization

---

# License

MIT License
