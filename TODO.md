I have integrated the **Remote Host (DigitalOcean)** requirements into your `TODO.md`. This update reflects the shift from a purely local development environment to a **Target Deployment** strategy, ensuring we don't repeat the "collision zone" mistakes found in your terminal history.

---

### 📝 UPDATED TODO.md

## 🟩 PHASE 1: INFRASTRUCTURE & DX (COMPLETED)
- [x] **Registry Fix:** Namespace aligned to `App\Core\Registry`.
- [x] **Makefile DX:** Added `pull-lean`, `test-infra`, and `git-push`.
- [x] **Remote Access:** SSH Keys linked and Droplet connectivity verified.

## 🟨 PHASE 2: THE NEURAL PIPELINE (CURRENT SPRINT)
- [ ] **The PHP Handshake:** Update `UploadController` to trigger `ai:8000/process` via `Registry`.
- [ ] **Python Logic:** Finalize the FastAPI endpoint to receive file paths and initiate `all-MiniLM` embedding.
- [ ] **Qdrant Ingestion:** Implement the "Upsert" logic to store vectors with document metadata.
- [ ] **Error Tracking:** Use the "Three Terminal" method (PHP, AI, Qdrant) to debug the end-to-end flow.

## 🟧 PHASE 3: DIGITALOCEAN DEPLOYMENT (NEW)
- [ ] **Server Sanitization:** Remove legacy artifacts (`/var/www/node`, `/var/www/python_mvc`) to prevent port 80/443 collisions.
- [ ] **Production Env:** Create `.env.prod` with `phi3:mini` and `all-minilm` configurations.
- [ ] **SSL Restoration:** Re-provision Let's Encrypt certificates for `sharpishly.com` using Certbot inside the Nginx container.
- [ ] **Swap Configuration:** Verify 4GB ZRAM/Swap partition is active to prevent Ollama from crashing the 1GB RAM Droplet.

## 🟦 PHASE 4: UI & RETRIEVAL (UPCOMING)
- [ ] **SPA Query Interface:** Build the "Ask a Question" input in Vanilla JS.
- [ ] **RAG Response:** Connect the Python service to Ollama for context-aware answers based on uploaded docs.

