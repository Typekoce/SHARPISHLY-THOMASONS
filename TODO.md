This is a **solid production-grade TODO**, but for a **1GB RAM / 1vCPU droplet** we need to **re-order priorities** or the system will never stabilize. Right now you're mixing:

* infra survival tasks
* UI work
* CRM features
* AI pipeline
* deployment

On **micro hardware**, the **boot order matters** or you'll chase phantom bugs.

Here's the **correct execution order** for Sharpishly V3 Micro-Neural. 🧠⚙️

---

# 🥇 Phase 1 — Host Survival (DO THIS FIRST)

Nothing else matters until this is stable.

Priority:

```
[ ] Create 4GB swap
[ ] Install ZRAM
[ ] Limit Ollama concurrency
[ ] Set docker mem limits
```

This prevents:

* OOM killer
* container restart loops
* corrupted MySQL
* Neo4j crashes

Your swap setup is correct:

```bash
sudo fallocate -l 4G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

Then **add swappiness** (VERY important):

```bash
echo vm.swappiness=10 | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

This stops swap thrashing.

---

# 🥈 Phase 2 — Ollama Micro-Neural Lockdown

docker-compose MUST include:

```yaml
environment:
  - OLLAMA_NUM_PARALLEL=1
  - OLLAMA_MAX_LOADED_MODELS=1
  - OLLAMA_KEEP_ALIVE=5m
mem_limit: 800m
```

This is the **difference between stable vs constant crashes**.

---

# 🥉 Phase 3 — Organism Heartbeat (Critical)

This becomes your **system governor**.

It should return:

```json
{
  "status": "ok|throttled|busy",
  "memory_free_mb": 120,
  "cpu": 0.92,
  "queue_depth": 3
}
```

Throttle rule:

```
if MemAvailable < 120MB
    status = "throttled"
```

PHP then:

```
if throttled → queue job
if ok → process now
```

This prevents **AI self-DDOS**.

---

# 🏗️ Phase 4 — AI Pipeline Migration

Only after memory stability:

```
[ ] Logic Migration PHP → Python
[ ] Decommission ChunkingService
[ ] Decommission EmbeddingService
[ ] Decommission VectorDb
```

This reduces:

* PHP memory
* duplication
* container overhead

Huge win on 1GB.

---

# 🧠 Phase 5 — Model Quantization (Final AI step)

Switch to:

```
phi3:mini-q4_K_M
all-minilm
```

Do NOT use:

```
llama3
mistral
nomic-embed-text
```

They will crash 1GB nodes.

---

# 🚨 Important — Neo4j is Dangerous on 1GB

Right now you have:

```
Neo4j heap max: 2G
```

That alone will kill the droplet.

Change to:

```yaml
NEO4J_server_memory_heap_initial__size: 128m
NEO4J_server_memory_heap_max__size: 256m
NEO4J_server_memory_pagecache_size: 128m
```

Otherwise Neo4j alone consumes entire RAM.

This is **critical**.

---

# Correct Priority TODO (Reordered)

## Phase 1 — Survival

```
[x] Dockerize Worker
[x] Neural Model Fix
[ ] Create 4GB Swap
[ ] Install ZRAM
[ ] Set OLLAMA_NUM_PARALLEL=1
[ ] Set docker mem limits
[ ] Reduce Neo4j heap to 256m
```

## Phase 2 — Stability

```
[ ] ai/organism.py heartbeat
[ ] Memory guard
[ ] Redis serialization
```

## Phase 3 — AI Pipeline

```
[ ] PHP → Python migration
[ ] Decommission PHP AI services
```

## Phase 4 — Deployment

```
[ ] GitHub actions
[ ] docker prune automation
```

## Phase 5 — UI + CRM

```
[ ] Menu refactor
[ ] Landlord CRM
[ ] breadcrumbs
```

UI should be **last** on micro hardware.

---

# Your Architecture (Now Correct)

```
PHP (light)
   ↓
Redis queue
   ↓
Organism controller (governor)
   ↓
Ollama (quantized)
   ↓
Neo4j (256MB heap)
```

This fits in **~900MB total**.

---

# Final Recommendation

Yes — proceed with:

```
[ ] ai/organism.py
```

But **BEFORE THAT** fix:

1. swap
2. Neo4j memory
3. Ollama limits

Otherwise organism will be trying to control a **crashing system**.

---

If you'd like, I can now generate:

* `OrganismController.py`
* memory guard
* Redis queue reader
* heartbeat endpoint

All optimized for **1GB RAM**.
