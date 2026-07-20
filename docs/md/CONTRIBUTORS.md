### CONTRIBUTORS

# Introduction

* The basic functionality of this framework has been established.


* Please restrict your development to the PHP, Python, JavaScript models, controllers & views.


* Refer to working examples in each folder.


* Refer to the Makefile to see how to create new workers.



# Core Architectural Pillars

* **MVC & DRY:** All new behavior must live exclusively within PHP, Python, or JS models, controllers, and views without mixing logic into random scripts or configuration files. Mirror existing patterns (e.g., `GoogleapiController.php`, `HotmailapiController.php`, `AzureapiController.php`, `HealthController.js`) for naming, method structure, and JSON responses. Reuse shared helpers like `BaseController`, `BaseCloudController`, and standard `curlRequest` patterns instead of duplicating HTTP logic.


* **Zero Bloat / No Native Framework Dependencies:** Do not introduce heavy third-party SDKs (such as Stripe PHP SDK, Google PHP Client, or AWS SDK) into the main repository; rely instead on raw HTTP and lightweight helpers. Composer usage is strictly managed via GitHub Actions and CI, never installed or executed locally per-developer. Raw SQL is strictly prohibited; utilize existing decoupled data models and schema generators.


* **Schema-Driven Integration:** Every new cloud, financial, or API integration must extend `BaseCloudController` (or its equivalent base) and utilize a unified `secureRequest()` pattern rather than bespoke cURL controller code. Cloud providers (Azure, AWS, Google Cloud, DigitalOcean, Stripe, Xero, etc.) are registered through abstract patterns or schemas. Secrets for these providers must be loaded via abstract secret services or vaults rather than hard-coded constants.


* **Lateral Thinking & Simplicity:** Prefer filesystem queues, workers, and Supervisor/Systemd over complex orchestration stacks. Build thin controllers that express intent and delegate to workers/agents while avoiding pushing CLI concerns into web controllers.



# Security Expectations & Policies

* **Secrets & Config:** Never commit client IDs, client secrets, API keys, or tokens. They must reside in local, ignored files (such as `env.php`) or external secure stores.
* **Policy Enforcement:** `.secrets_policy` acts as the single source of truth for forbidden patterns (Stripe, Xero, and generic secrets). Local hooks (`pre-commit` and `pre-push`) must remain active and enforced.


* **Breach Protocol:** If GitHub push protection blocks a push, treat it as a breach-in-progress: scrub the secret from history, rotate credentials in the provider, and verify via grep before attempting to push again.


* **Canonical Hooks:** The `pre-commit` hook scans staged files for patterns from `.secrets_policy` and exits non-zero to abort the commit. The `pre-push` hook scans the outbound commit range to block pushes if any historical commit contains secrets. Never move security checks into `commit.sh` or wrapper scripts; Git hooks are the sole canonical enforcement point.

# Tasks & Roadmap

* **Worker Centralization:** Centralize directory-polling and worker loops (including Himalaya, RAG, and job queues) into a single, configurable worker script (`supervisor_worker.sh`) managed by Supervisor. This script must read job files from a structured queue directory, route them by type to the correct handler, and write outputs/status back in a predictable format.


* **Cloud & Financial Agents/Controllers:** Create intelligent agents and controllers for Azure, AWS, Google Cloud, DigitalOcean, Stripe, Xero, etc. Each must extend `BaseCloudController` and implement `authenticate()` (for OAuth2, signed requests, etc.) and `getEndpoint()`. These controllers must expose JSON-only endpoints and rely on shared secret abstractions instead of environment constants.
* **React/Node + NATS Bridge:** Initialize `/mobile` for the React/Node stack, treating Node as the I/O bridge (frontends, WebSockets, mobile clients) and PHP as the core application brain. Use messaging brokers between Node and PHP workers where frontends talk to Node, Node emits jobs/events to PHP, and PHP writes back state for Node to surface.



# Development Workflow Reminder

* Restrict work strictly to PHP, Python, and JavaScript models, controllers, and views. If something does not fit within these boundaries, reconsider the design.


* Match structural patterns of closest existing components and use the Makefile for creating new workers rather than ad hoc scripts.


* Before pushing or opening pull requests, run local validation scripts, verify hooks, and confirm that no secrets are present in tracked files.