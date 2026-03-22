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

---
*Note: This file is a living document. Refer to it before suggesting any solution to ensure compliance with the "Thomasons" structural standard.*
