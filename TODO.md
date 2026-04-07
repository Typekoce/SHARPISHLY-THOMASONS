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

That 1GB/1CPU Droplet is a classic "lean" environment. It’s perfect for a highly optimized PHP/Nginx stack, but Ollama is going to be the hungriest guest at this table. With only 1GB of RAM and 25GB of disk, we need to be surgical. llama3.1 (8B) usually wants ~4.7GB of RAM, and nomic-embed-text wants ~270MB. On a 1GB machine, the OS and Docker already take a chunk, so we are going to rely heavily on SWAP and Quantization.

🛠️ Critical VPS Tuning for the Neural Pipeline
1. The Swap Space (Safety Net)
Without a Swap file, Ollama will likely trigger the Linux OOM (Out of Memory) killer and crash your whole Docker stack the moment it tries to load a model.
Action: Create a 4GB Swap file immediately on the host:

Bash
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
2. Model Quantization (The "Q4_K_M" Plan)
Standard models are too heavy. From your TODO under Optimization Roadmap, ensure you are pulling the quantized versions. They use significantly less RAM with almost no noticeable loss in "Neural" quality for document processing.

Instead of llama3.1:latest, try pulling llama3.1:8b-instruct-q4_K_M if memory pressure gets too high.

3. Storage Management
You have 25GB of disk.

Llama 3.1: ~5GB

Nomic Embed: ~0.5GB

OS + Docker Images: ~5-8GB

Remaining: ~10-12GB for your MySQL data and uploads.

Tip: Regularly run docker system prune -f (already in your GitHub Actions plan) to keep that 25GB from hitting 100%.

🧬 Updated Neural Pipeline Strategy
Since your Droplet is tight on resources, the ai/organism.py (The Heartbeat) becomes even more important. It shouldn't just listen; it should throttle.

Updated src/Controllers/OrganismController.py thought process:
We should add a "Memory Guard" to the heartbeat. If free RAM is < 100MB, the Organism should report status: "throttled" to the PHP side so the UI can tell the user "System busy, please wait."

📝 TODO Update (Infrastructure)
[ ] Host Setup: Create 4GB Swap file on LON1 Droplet.

[ ] Ollama Config: Set OLLAMA_NUM_PARALLEL=1 in docker-compose.yml to prevent it from trying to process multiple requests and exploding the RAM.

[ ] Memory Monitoring: Add memory_usage to the OrganismController heartbeat.

🚀 Commit Message for Infrastructure Prep
If you've applied these host-level changes:

Plaintext
ops: optimize LON1 droplet for neural workload

- Configure 4GB swapfile to handle LLM memory spikes
- Update TODO with RAM-sensitive orchestration requirements
- Prepare for Q4_K_M model quantization to fit 1GB RAM footprint
How is the download progressing on that 1GB node? If it feels like the terminal is hanging, it’s likely the CPU maxing out while it decompresses the model layers. Just give it air—it’ll get there!