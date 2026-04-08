# SHARPISHLY-THOMASONS V3: PROJECT CONTEXT
**Target Release:** March 13, 2026

## 🎯 MISSION
A self-hosted, privacy-first Neural Pipeline. High-performance local inference with a "lean" VPS footprint (1GB RAM). 

## 🏗️ ARCHITECTURE & CONSTRAINTS
- **Zero External Dependencies:** No Composer (local), no NPM. Vanilla JS and Native PHP only.
- **Service-Oriented:** - `PHP (The Brain)`: MVC, Registry Pattern, Job Dispatching.
    - `Python (Cognition)`: FastAPI "Organism," LangChain, Ollama.
- **Storage:** Absolute root `/var/www/html/storage/` (mapped 1:1 to host `./storage`).
- **Data Integrity:** No Raw SQL. Use `App\Models` with PDO Prepared Statements.
- **Permissions:** All processes must run as `www-data` (UID 33).

## 🚦 ROUTING & REGISTRY
- **Registry:** Strictly `App\Core\Registry`. Centralized singleton management.
- **Routing:** 3-Tier (Subdomain -> Special Paths -> Slug-based Auto-mapping).
- **Logging:** Standard Output (`stderr`) for Docker centralization.

## 🧠 NEURAL STACK (The Elastic Brain)
- **Local:** Llama 3.1 8B + Nomic-Embed.
- **VPS (1GB):** Phi-3 Mini (Q4) + all-MiniLM + 4GB Swap/ZRAM.