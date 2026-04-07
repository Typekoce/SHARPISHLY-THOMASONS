# Sharpishly V3 – Master Project TODO

**🚨 CRITICAL DEADLINE: Friday, March 13, 2026**

## Infrastructure & Reliability 🏗️

- [x] Dockerize Worker: Map `worker.php` as background service in `docker-compose.yml`
- [x] Neural Model Fix: Added `llm` service with persistent volume mapping
- [ ] `ai/organism.py` Implementation: Map to `src/Controllers/OrganismController.py` (index/heartbeat method)
- [ ] Ollama Timeout Workaround: Mock responses or async handling for VM timeouts
- [ ] Log Aggregation: Centralize logs, uploads, and storage structure
- [ ] Storage Consolidation: Map all data-heavy directories
- [ ] Dockerize `worker.php` as background service (docker-compose.yml)

## Frontend & UI Consolidation 🎨

- [x] Handshake Dashboard: Neural Pipeline v3.5 monitor in `script.js`
- [ ] Menu Refactor: Limit to exactly 5 main links with submenus
- [ ] Navigation: Add breadcrumb components to all pages
- [ ] Core Completion: Finalize API, Messages, Server, Client functionality
- [ ] Handle URL Parameters: `/profile/123` → pass IDs to Controllers
- [ ] Form Submissions: Send data back to PHP via POST requests

## Landlord CRM Module 🏠

- [ ] Implementation: Build Landlord CRM logic
- [ ] Database Integrity: Wrap `HomeModel` migrate/alter in try/catch
- [ ] Add Index: `idx_job_id` on `csv_records`
- [ ] Add Foreign Key: `fk_job_id` on `csv_records` → `jobs(id)`

## Decommissioning 🧹

- [ ] Logic Migration: PHP → Python (`TextProcessor`/`NeuralWorker`)
- [ ] Decommission `ChunkingService.php`
- [ ] Decommission `EmbeddingService.php`
- [ ] Decommission `VectorDb.php`
- [ ] Decommission `WordDocService.php`
- [ ] Scaffold Removal: Decommission `scaffold/migrate` (now in `HomeController`)

## Testing & Quality 🚀

- [ ] Upload Testing: Verify 100% document ingestion progress
- [ ] RAG Verification: Test Neural Chat + vector retrieval
- [ ] Git Metadata: Extract commit hash, branch, dirty flag (Location/Environment)

## Documentation 📚

- [ ] README: Add architecture diagrams + API details

## GitHub Actions Deployment (Option B) 🚀

1. **Create**: `.github/workflows/deploy-to-digitalocean.yml`
2. **Secrets**:
   - `SSH_HOST`: Droplet IP
   - `SSH_USERNAME`: `root`
   - `SSH_PRIVATE_KEY`: Private SSH key
   - `SSH_PORT`: `22`
3. **Features**:
   - Manual/Push triggers
   - `git reset --hard` clean state
   - Docker build/pull orchestration
   - `docker system prune` cleanup
   - Local health check verification

## Optimization Roadmap ⚡

### Infrastructure (Nginx & System)
- Nginx Worker Tuning: `worker_processes` + `worker_connections`
- Gzip/Brotli Compression: Static SPA assets
- PHP-FPM Pool Tuning: `pm.max_children` + `pm.start_servers`

### Application Logic
- Script Consolidation: Minify `script.js`, remove dead code
- PHP OpCache: Enable/tune in Docker PHP image
- Database Indexing: Audit MySQL `csv_records` lookups

### Resource Services
- Ollama: Use Q4_K_M quantization for VPS RAM
- Async Workers: CSV stream processing (avoid full file loads)

### Deployment (Digital Ocean)
- Docker Resource Limits: RAM/CPU in `docker-compose.yml`
- CI/CD: GitHub Actions pipeline
- Monitoring: Log rotation to prevent disk saturation

***

**Next Immediate Action**: Create `src/Controllers/OrganismController.py` for heartbeat mapping.