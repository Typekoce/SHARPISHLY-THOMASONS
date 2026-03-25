# SHARPISHLY-THOMASONS V3: PROJECT CONTEXT

## 🎯 MISSION
A professional-grade, service-oriented architecture for a Neural Pipeline. It transforms raw data (CSV/TXT) into searchable vector embeddings using a decoupled PHP "Brain" and a Python/LangChain "Cognition" layer.

## 🏗️ ARCHITECTURE & CONSTRAINTS
- **Zero External JS/PHP Libraries:** No Composer (locally), no NPM, no external frameworks. Use Vanilla JS and Native PHP.
- **Separation of Concerns:** - `/web/frontend`: The Skin (Vanilla JS SPA, Bootstrap 5 CSS-only).
    - `/web/php/src`: The Brain (MVC, PSR-style Autoloader, Tiered Router).
    - `/ai` & `/llm`: The Cognition (Python, Ollama, Qdrant).
- **Centralized Storage:** All volatile data (uploads, logs, temp) must reside in the root `/storage` directory, NOT within the application folders.
- **No Raw SQL:** All database interactions must be handled via Model methods or prepared statements (No `query("")` strings in Controllers).
- **Registry Pattern:** Classes are instantiated once via `App\Registry`.

## 📂 DIRECTORY MAP
- `web/frontend/`: `index.html`, `script.js`, `styles.css`.
- `web/php/src/`: Entry point `index.php`, `bootstrap.php`, and MVC directories.
- `storage/logs/`: Central hub for all system and application logs.
- `infra/`: Docker configurations for Nginx, MySQL, and PHP.

## 🚦 ROUTING LOGIC (index.php)
1. **Tier 1:** Subdomain-based (e.g., `docs.sharpishly.vm`).
2. **Tier 2:** Hard-coded Special Paths (e.g., `/neural`, `/jeff_bezo`).
3. **Tier 3:** Slug-based Auto-mapping (`/controller/method/params`).

## 🧠 NEURAL PIPELINE STAGES (SPA)
1. **Upload:** Move file to `storage/uploads`.
2. **Chunk:** PHP breaks data into logical segments.
3. **Embed:** AI generates high-dimensional vectors.
4. **Index:** Vectors are stored in the Vector DB (Qdrant).

## 🛠️ CRITICAL PATHS
- **PHP Index:** `/var/www/html/web/php/src/index.php`
- **Storage Base:** `/var/www/html/storage/`
- **Frontend Root:** `/var/www/html/web/frontend/`

## ⚖️ DESIGN DECISIONS
- **Logging Strategy (2026-03-21):** Pivoted from persistent file-based logging (`storage/logs`) to ephemeral standard output (`stderr`). This bypasses Docker/Host permission conflicts and centralizes debugging via `docker compose logs -f php`.
- **Infrastructure:** Using a minimalist `dev-up.sh` for atomic environment resets (down -v, up --build).

## System Status (2026-03-22)
- **Routing:** Functional. Minimalist mapping logic implemented in `web/php/src/index.php`.
- **Database:** Routing to DB service confirmed. Currently blocked by missing `pdo_mysql` driver in PHP container.
- **Frontend:** SPA queue system ready for live job ID polling.

## Technical Constraints
- No Composer local installation; using GitHub Actions for autoloading.
- Raw SQL is strictly forbidden; using PDO prepared statements.
- Async processing via `php-worker` (pending service definition).

## System Status (2026-03-23)
- **Infrastructure:** BROKEN. Despite reverting to "known-working" YAML and .env, the PHP container is failing to resolve the database hostname.
- **Blocker:** DNS/Name resolution failure within the Docker bridge network.
- **Progress:** Zero. The afternoon was spent troubleshooting credential and networking loops that did not result in a successful migration.

Proposed Additions to CONTEXT.md:
Infrastructure-First Policy: Always verify docker-compose.yml volume mappings and Dockerfile permissions before proposing path-dependent PHP services.

Registry Initialization Order: The Location and Smarty services must be registered before the Database service to prevent recursive dependency crashes.

Storage Architecture: The absolute root for all persistence is /var/www/html/storage/, mapped 1:1 from the host ./storage directory.

User Permissions: All PHP processes and file creations must stay within the www-data (UID 33) scope to maintain cross-environment compatibility (Tardis vs. Local).

I hear the frustration, and you are right to call it out. We spent today fighting the "plumbing" of the architecture rather than building the "Neural Pipeline" features we intended.

When an AI and a developer hit a loop like this, it’s usually because the Internal Project Map (my understanding of your files) has become de-synced from the Physical Reality (the files actually sitting on your Tardis VirtualBox).

Here is the "post-mortem" of why we stalled today and how we fix the trajectory.

🔍 The Performance Breakdown: Why We Looped
1. The Namespace "Ghost" (App\Registry vs. App\Core\Registry)
This was the primary time-sink.

The Failure: I suggested moving the Registry to the Core folder to follow PSR-4 standards. However, I failed to proactively audit every single file (BaseController, ScaffoldController, Migrator) to ensure the use statements were updated simultaneously.

The Result: Every time we fixed one file, a "Class Not Found" error popped up in another, creating a "Whack-a-Mole" effect that made us look like we were standing still.

2. The Docker-PHP "Silent Handshake"
The Failure: I didn't account for the aggressive try/catch block in your bootstrap.php.

The Result: When MySQL failed to connect (likely due to a password mismatch or the container not being "Ready"), the code silently fell back to DbJson. Because DbJson and Db have different methods, the app didn't just "fail"—it "failed weirdly" with Undefined Method errors, which are harder to track than a simple "Connection Refused."

3. Permission & Volume Friction
The Failure: We pivoted between running as root and running as www-data in the Dockerfile.

The Result: This created a situation where the PHP process sometimes couldn't write to /storage/logs, meaning the very errors we needed to see were being swallowed by the filesystem.

🛠️ The Corrective Project Map
To ensure tomorrow isn't a repeat of today, we are going to treat the Registry and Permissions as "Settled Law."

The "Thomasons V3" Source of Truth:
Registry: Is strictly App\Core\Registry. No exceptions.

BaseController: Now explicitly imports App\Core\Registry.

Database: We are committed to MySQL. We will no longer use DbJson as a fallback in the bootstrap.php because it masks infrastructure errors. If the DB is down, we want the app to scream, not "limp" along.

📋 The "Stalled" Tasklist (Status Check)
We are currently stuck at Step 2. We cannot move to Step 3 until the SQL actually lands in the DB.

Phase	Task	Status	Obstacle
1. Registry	Namespace Alignment	⚠️ PARTIAL	Dependency on BaseController update.
2. Schema	Run SQL Migrations	❌ FAILED	MySQL Connection / Type mismatch.
3. Ingestion	FileAgent & FileStaging	📅 Pending	Blocked by Step 2.
4. Neural	Job Queue Processing	📅 Pending	Blocked by Step 2.
💡 A Grounded AI Perspective
I failed to "see the whole board" today. I treated each error as an isolated incident instead of recognizing that the Registry move was a breaking change for the entire inheritance chain. That cost us time.

For our next session, would you like me to provide a single, unified "Bootstrap & Base" package that forces MySQL connectivity and removes the fallbacks, so we can finally see those green checkmarks in the migration? ☕🚀

---
*Note: This file is a living document. Refer to it before suggesting any solution to ensure compliance with the "Thomasons" structural standard.*
