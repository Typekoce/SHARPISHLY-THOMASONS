# Project Management Guidelines (Linear Workflow)

This document defines the issue-tracking conventions for the **Sharpishly** platform using **Linear**. The structure mirrors our background queue execution semantics (`waiting` → `processing` → `completed`) and multi-host deployment topology (`@seaview`, `@foozie`, `@maxie`).

---

## 1. Status Mapping & Queue Semantics

| Linear Status | Queue Equivalency | Description |
| :--- | :--- | :--- |
| **Backlog** | Unscheduled | Future improvements, refactoring, or unscheduled feature requests. |
| **Ready** | `storage/cmd/jobs/waiting/` | Actionable task ready for immediate execution or implementation. |
| **In Progress** | `storage/cmd/jobs/processing/` | Currently being worked on locally or actively executing in worker thread. |
| **Done** | `storage/cmd/jobs/completed/` | Merged, deployed, and verified operational across target hosts. |
| **Canceled** | Dead-letter / Discarded | Task rendered obsolete, duplicated, or superseded by another change. |

---

## 2. Host & Module Labeling Schema

Every issue must be tagged with at least one **Host** and one **Area** label to clarify target node execution requirements.

### **Host Labels (`host:*`)**
* `host:seaview` – Primary development node, PHP API, local worker host.
* `host:foozie` – Secondary application host / domain gateway (`https://sharpishly.dev/`).
* `host:maxie` – Remote provisioning target (`192.168.0.90`).

### **Area Labels (`area:*`)**
* `area:infra` – Shell utilities, SSH setup, workers, systemd, and cron scheduling.
* `area:rag` – Vector DB (Qdrant), embedding models, `KnowledgeRetriever`, text processing.
* `area:ollama` – Local LLM execution, payload formatting, `Orm.php` integration.
* `area:workers` – Background processing scripts (`ingestion_worker.py`, `cmd_worker`).
* `area:mobile` – Mobile web interfaces, API entry points, header layouts.

---

## 3. Git & Pull Request Integration Conventions

Linear automatically transitions issue states when commits or pull requests include the Linear issue ID (e.g., `SHA-101`).

* **Branch Naming:** `<type>/<issue-id>-<short-description>`
  * *Example:* `feat/SHA-12-maxie-sudoers-provision`
  * *Example:* `fix/SHA-45-php-fpm-package-missing`
* **Commit Messages:** Include the Issue ID in the header or footer.
  * *Example:* `fix(infra): add ondrej/php PPA to final_installation on @maxie (SHA-45)`
* **Pull Request Closing Keyword:** 
  * *Example:* `Fixes SHA-45` (Moves Linear task to **Done** automatically upon merge).

---

## 4. Standard Issue Templates & Examples

### **Example 1: Infrastructure & Host Provisioning**

* **Title:** Fix PHP 8.3 package resolution on `@maxie`
* **Status:** `Ready`
* **Labels:** `host:maxie`, `area:infra`
* **Description:**
  ```text
  ### Objective
  Update the ./final_installation script to resolve missing php8.3-fpm dependencies on @maxie.

  ### Commands / Execution
  sudo add-apt-repository ppa:ondrej/php -y
  sudo apt-get update
  sudo apt-get install -y php8.3-fpm php8.3-mysql php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip

  ### Target Queue / Path
  storage/cmd/jobs/waiting/maxie_php_fix.sh