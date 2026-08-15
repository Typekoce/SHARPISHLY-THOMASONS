This is the definitive update to the **CONTEXT.md**. I have stripped the "God Object" legacy and replaced it with a stark warning about the `Registry` failure to serve as a permanent guardrail for the remainder of this build.

---

# SHARPISHLY-THOMASONS V3: PROJECT CONTEXT
**Current Version:** 3.1.0 (Post-Registry)
**Last Audit:** April 9, 2026

## 🎯 MISSION
A professional-grade, service-oriented architecture for a Neural Pipeline. Decoupled PHP "Brain" (MVC) and Python/LangChain "Cognition" layer for high-performance local vector inference.

## 🏗️ ARCHITECTURE & CONSTRAINTS
- **Zero External JS/PHP Libraries:** No Composer (locally), no NPM. Pure Vanilla JS and Native PHP.
- **Separation of Concerns:** - `/web/frontend`: The Skin (Vanilla JS SPA).
    - `/web/php/src`: The Brain (MVC, PSR-style Autoloader).
    - `/ai` & `/llm`: The Cognition (Python, Ollama, Qdrant).
- **Direct Service Injection:** **DEPRECATED REGISTRY PATTERN.** Controllers must directly instantiate required Services or use static Singletons where appropriate.
- **Centralized Storage:** Absolute root `/var/www/html/storage/` (mapped 1:1 to host `./storage`).
- **Data Integrity:** No Raw SQL. Strict use of PDO Prepared Statements within `App\Models`.

## 📂 DIRECTORY MAP
- `web/frontend/`: `index.html`, `script.js`, `styles.css`.
- `web/php/src/`: Entry point `index.php`, `bootstrap.php`.
- `storage/`: `uploads/`, `logs/`, `temp/`.
- `infra/`: Docker configurations (Nginx, MySQL, PHP, AI).

## ⚖️ DESIGN DECISIONS & POST-MORTEMS

### 🛑 THE REGISTRY "NIGHTMARE" (2026-03-23 to 2026-04-08)
The project suffered a **two-week total stagnation** due to the `App\Core\Registry` pattern. 
* **The Failure:** It acted as a single point of failure and a "black box" for dependency loops. Namespace shifts (App\Registry vs App\Core\Registry) caused a "Whack-a-Mole" error chain across the inheritance tree.
* **The Decision:** The Registry has been **purged**. Its removal is a "Settled Law" of the architecture. 
* **The Lesson:** Avoid "God Objects" in low-resource environments. Explicit dependency instantiation provides clearer stack traces and faster debugging.

### 🛡️ INFRASTRUCTURE-FIRST POLICY
- **Fail Fast:** Removed silent fallbacks (like `DbJson`). If the MySQL connection fails, the app must crash immediately to prevent "weird" secondary errors.
- **Permission Scope:** All processes must stay within `www-data` (UID 33).
- **Logging:** Ephemeral `stderr` logging only. Do not rely on persistent log files for real-time debugging.

## 🧠 NEURAL PIPELINE STATUS
1. **Upload:** Managed by `UploadController`.
2. **Handshake:** PHP triggers Python `/process` endpoint.
3. **Embedding:** Python handles Nomic/MiniLM vectors.
4. **Storage:** Vectors pushed to Qdrant; metadata synced to MySQL.

## 🚦 SYSTEM STATUS (2026-04-09)
- **Infrastructure:** Recovered from `DISK_FULL` and `Inaccessible` VM states.
- **Memory:** VM RAM increased; VPS Swap/ZRAM pending verification.
- **Database:** `pdo_mysql` confirmed.
- **Task:** Implement `ProvisionController.php` to audit environment health (Docker, Ollama, Models).

---

### 🚦 The "First Strike" of the Day
Now that the context is accurate and the "Registry Ghost" is exorcised:

1.  **Find your VM IP:** Use that `ip addr show | grep inet` we discussed.
2.  **Verify SSH:** Get back into the Tardis terminal.
3.  **Start the Build:** Run `make clean` (the new scorched-earth version) then `make up`.

**What is the IP address? I need to know if we're on a Bridged or NAT network to predict our next SSH hurdle.**

### 📁 DURABLE STORAGE & ASYNC WORKER ARCHITECTURE
- **`storage/` Staging Buffer:** Serves as the durable landing zone for RAG document ingestion (PDFs, raw datasets) prior to text extraction, chunking, and vector database insertion.
- **Async Isolation:** Long-running compute (vectorization) and rate-limited side-effects (Email, SMS, queued background tasks) belong in asynchronous queues serviced by dedicated workers.
- **Web Lifecycle Boundary:** Keeps HTTP API endpoints thin, fast, and strictly synchronous, avoiding PHP timeouts and memory spikes.