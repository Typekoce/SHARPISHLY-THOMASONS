Here's a much more **readable, professional, and user-friendly** `README.md` version for your project:

---

# 🧬 Temporal GraphRAG Knowledge Organism

A production-grade Retrieval-Augmented Generation (RAG) system that intelligently handles **document versions** and **temporal evolution** using a hybrid architecture:

- **PHP** – System of Record (web backend)
- **Python + LangGraph** – The intelligent "Brain"
- **Neo4j** – Temporal Knowledge Graph
- **MariaDB** – Structured metadata and business data

---

## 📋 Overview

This system solves the common "Version Hell" problem in RAG applications. Instead of blindly retrieving chunks, it understands:
- Which document version is the **latest/current**
- How documents **supersede** older ones over time
- User intent (Fact, Summary, or Action)
- Contradictions via an **Antagonist Agent**

The result is a reliable, self-correcting Knowledge Organism that stays accurate even as your documents evolve.

---

## 🏗️ Project Structure

```bash
.
├── docker-compose.yml
├── .env
├── CONTEXT.md
├── bin/
├── web/                 # PHP Backend (System of Record)
├── ai-engine/           # Python Neural Layer (The Brain)
│   ├── app.py
│   ├── organism.py
│   ├── processor.py
│   └── nodes/
├── database/
├── dashboard/           # Streamlit visualization
└── README.md
```

---

## 🚀 Quick Start

### 1. Environment Setup

Copy and configure the environment variables:

```bash
cp .env.example .env
```

Edit `.env` with your credentials (API keys, database passwords, etc.).

### 2. Start All Services

```bash
docker compose up --build -d
```

Services will be available at:
- **PHP App**: http://localhost:8080
- **AI Engine API**: http://localhost:8000
- **Dashboard**: http://localhost:8501
- **Neo4j Browser**: http://localhost:7474

### 3. Ingest Documents

Use the ingestion endpoint or PHP upload interface to add documents. The system will automatically:
- Chunk the documents
- Generate embeddings
- Create temporal relationships (`SUPERSEDES` edges)
- Store metadata in MariaDB and the graph in Neo4j

### 4. Ask Questions

You can query the system via:
- The PHP frontend
- Direct API call to `/query`
- The Streamlit dashboard

---

## 🔧 Core Components

### Knowledge Graph (Neo4j)

The heart of the system. It tracks document versions and their relationships using `SUPERSEDES` edges.

**Example: Finding the latest version of a document**

```python
from neo4j import GraphDatabase
import os

class KnowledgeRetriever:
    def __init__(self):
        self.driver = GraphDatabase.driver(
            os.getenv("NEO4J_URI", "bolt://neo4j:7687"),
            auth=(
                os.getenv("NEO4J_USER", "neo4j"),
                os.getenv("NEO4J_PASSWORD")
            )
        )

    def get_latest_version(self, doc_id: str):
        """
        Returns the most recent version of a document by following the graph.
        Only returns chunks that are not superseded by any newer version.
        """
        query = """
        MATCH (c:Chunk {document_id: $doc_id})
        WHERE NOT (c)<-[:SUPERSEDES]-()
        RETURN c.content AS content, 
               c.valid_from AS valid_from,
               c.version_id AS version_id,
               c.llm_note AS note
        """

        with self.driver.session() as session:
            result = session.run(query, doc_id=doc_id)
            return result.data()

    def close(self):
        """Close the Neo4j driver connection."""
        self.driver.close()
```

**Usage example:**

```python
retriever = KnowledgeRetriever()
latest = retriever.get_latest_version("contract_2026_v3")
print(latest)
retriever.close()
```

---

## ✨ Key Features

- **Temporal Awareness** — Automatically tracks document versions and superseding relationships
- **Intent-Based Retrieval** — Routes queries to Fact, Summary, or Action vectors
- **Antagonist Agent** — Actively tries to find contradictions and triggers re-retrieval when needed
- **Self-Correcting LangGraph** — Loops until the context is reliable
- **Multi-Representation** — Stores Fact / Summary / Action embeddings per chunk
- **Full Traceability** — Every answer includes temporal provenance and antagonist feedback

---

## 📊 Monitoring & Visualization

Visit the **Dashboard** at `http://localhost:8501` to:
- Visualize the knowledge graph and `SUPERSEDES` relationships
- See live ingestion status
- Test queries with full transparency

---

## 🛠️ Development

### Rebuilding Services

```bash
# Rebuild only the AI engine
docker compose build ai-engine

# Restart everything
docker compose down && docker compose up --build -d
```

### Running Tests

```bash
# PHP tests
docker compose exec php ./bin/console test

# Python evaluation
docker compose exec ai-engine python -m pytest
```

---

## 📖 Learn More

- `CONTEXT.md` — Global architectural rules and source of truth
- `ai-engine/` — All LangGraph, retrieval, and agent logic
- `web/src/` — PHP controllers and services

---

**Made with ❤️ for reliable, version-aware AI assistance**

---

### How to use this README

1. Save the content above as `README.md` in your project root.
2. (Optional) Create an `.env.example` file with all the variables used in `.env`.

Would you like me to also create a clean `.env.example` file and improve any other documentation (like `CONTEXT.md` or inline docstrings in the Python files)?

Just say the word and I'll refine anything else!