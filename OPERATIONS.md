# Operations & Standard Procedures

This document outlines the standard operational procedures for the Sharpishly-Thomasons ecosystem. It complements the DISASTER_RECOVERY.md by focusing on routine maintenance, service management, and development standards.

## 1. Service Lifecycle Management
*   **Initialization:** Every service (API, Worker, RAG) must trigger `bootstrap.php` upon startup. This ensures the environment is grounded and constants are defined.
*   **Logging:** All services must output to `/storage/logs/`. Logs are audited via `make logs`.
*   **Database:** Use the internal `App\Services\Db` class. Connection pooling or singleton patterns should be used to prevent per-request connection overhead.

## 2. Development & Code Quality
*   **MVC & DRY:** All code must adhere to MVC principles. Controllers remain thin; logic resides in Models.
*   **Dependency Policy:** Minimalist approach. No heavy external libraries or complex message brokers (e.g., NATS).
*   **Path Management:** All file system path operations must use `App\Utils\LocationService` to prevent hardcoded directory dependencies.
*   **Autoloading:** Classes are not instantiated more than once. Autoloading is handled via `bootstrap.php` (no local Composer installation).

## 3. Deployment & Environment Rules
*   **Configuration:** Environment variables are strictly managed in `env.php`. Do not rely on system-level environment variables for application logic.
*   **Security:**
    *   `Security::applyHeaders()` must be enforced globally in `bootstrap.php`.
    *   Sensitive files (keys, secrets) must be ignored by Git.
    *   Pre-commit hooks are mandatory to prevent accidental credential leakage.

## 4. Maintenance Protocols
*   **Cleanup:** Never perform blanket wipes of system directories (`/private/var/folders/*`). Scripts requiring deletions must include explicit path validation.
*   **Task Management:** New background tasks must be implemented using the filesystem-polling queue pattern (`/storage/agents/.../waiting`).

## Standard Procedures Checklist
| Task | Procedure | Reference |
| :--- | :--- | :--- |
| **New Service** | Create class in `App\Services`, register in `bootstrap.php` | `bootstrap.php` |
| **New Route** | Add to `$aliases` in `index.php` or dynamic controller | `index.php` |
| **Path Access** | Always use `LocationService::...()` | `LocationService.py` |
| **Deployment** | Run via `make` or CLI, verify `logs` | `Makefile` |

---
*Note: This document is the source of truth for standard operational behavior. Adhere to these principles to maintain the project's minimalist architecture.*
