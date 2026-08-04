# Deployment Guide: Sharpishly Staging Setup (`@maxie`)

This document outlines the step-by-step procedure to deploy the Sharpishly framework to the staging environment host `maxie@192.168.0.90`.

## System Prerequisites & SSH Setup

Before triggering deployment endpoints, ensure the target host is reachable via SSH and configured for non-interactive execution.

### Host Access Map
- **Host IP**: `192.168.0.90`
- **User**: `maxie`
- **Repository**: `git@github.com:Typekoce/SHARPISHLY-THOMASONS.git`

---

## Deployment via Terminal API (`curl`)

You can trigger each phase of the deployment pipeline directly via HTTP `curl` requests targeting the local `TerminalController` loader endpoint (`http://localhost/php/terminal/load/{alias}`).

### 1. Configure Passwordless Sudo (One-Time Setup)
Grants non-interactive `sudo` rights to the `maxie` user on the remote host so `final_installation.sh` can execute system updates without hanging for a password prompt:

```bash
curl -i http://localhost/php/terminal/load/maxie-setup-sudoers