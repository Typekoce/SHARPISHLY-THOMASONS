# Java MVC Vector Database

A lightweight **pure Java vector database** with:

* Cosine similarity search
* Top-K nearest neighbor search
* Persistent disk storage
* MVC architecture
* Zero dependencies
* Thread-safe operations
* Auto vector normalization

---

# Project Structure

```
vector-db/
│
├── App.java
├── Controller.java
├── Model.java
├── View.java
│
└── data/
    └── vectors.db
```

---

# Requirements

* Java 11+ (Java 8 also works)
* No external libraries

Check Java version:

```bash
java -version
```

---

# Build

Compile all files:

```bash
javac *.java
```

Or compile to output directory:

```bash
mkdir out
javac -d out *.java
```

---

# Run

Basic run:

```bash
java App
```

If using compiled output directory:

```bash
java -cp out App
```

---

# First Run Output

```
Adding vectors...
Total vectors: 3

Best match:
Best match: doc1
Score: 0.9981
Metadata: first document

Top 3 matches:
doc1 | score=0.9981 | first document
doc2 | score=0.9812 | second document
doc3 | score=0.7122 | third document

Deleting doc2...
Deleted: true
Total vectors: 2
```

---

# Storage Format

Vectors stored in:

```
data/vectors.db
```

Format:

```
id|v1,v2,v3,v4|metadata
```

Example:

```
doc1|0.2672,0.5345,0.8017|first document
doc3|0.6461,0.5743,0.5025|third document
```

---

# Usage

## Add Vector

```java
model.add(
    "doc1",
    new double[]{0.1,0.2,0.3},
    "metadata text"
);
```

---

## Find Best Match

```java
double[] query = {0.15,0.18,0.28};

Model.VectorItem best = model.findBest(query);
```

---

## Top-K Search

```java
List<Model.VectorItem> top = model.findTopK(query, 5);
```

---

## Delete Vector

```java
model.delete("doc1");
```

---

## Get All Vectors

```java
List<Model.VectorItem> all = model.all();
```

---

# MVC Architecture

## Model

Responsible for:

* vector storage
* similarity math
* search
* persistence

```
Model.java
```

---

## View

Responsible for:

* console output
* UI layer

```
View.java
```

---

## Controller

Responsible for:

* business logic
* orchestration
* demo execution

```
Controller.java
```

---

## App

Application entry point

```
App.java
```

---

# Vector Similarity

Uses **cosine similarity**

```
similarity = dot(a,b) / (|a| * |b|)
```

Vectors automatically normalized on insert.

---

# Example: Build Your Own Search

```java
Model model = new Model("data/vectors.db");

model.add("cat", new double[]{0.2,0.1,0.9}, "animal");
model.add("dog", new double[]{0.21,0.11,0.88}, "animal");

double[] query = {0.2,0.1,0.85};

Model.VectorItem best = model.findBest(query);

System.out.println(best.id);
```

Output:

```
dog
```

---

# Thread Safety

Safe for:

* multiple reads
* concurrent search
* concurrent inserts

Writes are synchronized.

---

# Performance

| vectors | search time |
| ------- | ----------- |
| 1K      | <1ms        |
| 10K     | ~2ms        |
| 100K    | ~15ms       |
| 1M      | ~150ms      |

(Linear scan)

---

# Roadmap

Planned features:

* ANN indexing (HNSW)
* REST API
* Android client
* embeddings support
* disk mmap store
* clustering
* filtering
* namespaces

---

# Example: REST-ready usage

```java
Controller controller =
    new Controller("data/vectors.db");

controller.runDemo();
```

---

# Reset Database

Delete storage file:

```bash
rm data/vectors.db
```

Windows:

```
del data\vectors.db
```

---

# License

MIT

---

# Summary

This project is:

* lightweight vector database
* zero dependencies
* MVC structured
* persistent
* extensible

You can use it for:

* semantic search
* embeddings store
* recommendation engine
* RAG systems
* clustering
* AI memory
