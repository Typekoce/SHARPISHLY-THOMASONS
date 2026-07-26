# Code Review Guidelines & Architecture Baseline

## Overview

This document outlines the code review conventions for the Sharpishly framework. As the project moves from exploratory prototyping to a working demo, our code reviews should establish architectural baselines, standardize response contracts, and improve testability across our core controllers (`web/php/src/Controllers/`).

---

## 1. Core Principles

Our review process is guided by four foundational pillars:

1. **MVC & DRY:** Controllers orchestrate flow; models and services handle persistence and external I/O. Common helpers (e.g., `$this->json()`) must be reused across all endpoints.
2. **Review by Contract First:** Evaluate controllers by their inputs, outputs, and HTTP behavior before attempting massive internal rewrites.
3. **Incremental Isolation:** Wrap raw cURL executions, disk access, and global state in dedicated services or abstractions to keep controller surfaces minimal.
4. **Predictable Outcome Contracts:** Every public endpoint must return consistent JSON structures and explicit HTTP status codes under all execution paths.

---

## 2. File Classification Strategy

During review, every file in `web/php/src/Controllers/` must be assigned to one of three categories:

| Category | Description | Review Focus |
| :--- | :--- | :--- |
| **Active Workhorses** | Production endpoints, cloud integrations, and core logic. | Contract stability, HTTP error handling, and testability. |
| **Placeholders / Mocks** | Dev doubles and mock endpoints (e.g., `AwsapiController`). | Explicit mock markers (`'mock' => true`), payload shape consistency. |
| **Technical Debt / Dead Code** | Deprecated endpoints, worker scripts, or orphaned routines. | Immediate flag for removal or isolation. |

---

## 3. Controller Review Checklist

Apply this checklist to each controller during code review passes:

### A. Architecture & Responsibility
- [ ] **Class Inheritance:** Does the class extend `BaseController` or `BaseCloudController`?
- [ ] **Single Responsibility:** Does the controller only handle request parsing and output orchestration? Heavy business logic or raw API integration should reside in services/models.
- [ ] **Consistent Instantiation:** Does the class conform to project instantiation guidelines without forcing unnecessary duplication?

### B. Response & HTTP Behavior
- [ ] **Standardized Output:** Uses `$this->json()` rather than raw `echo`, `print_r`, or manual `header()` calls.
- [ ] **Explicit HTTP Status:** Returns appropriate status codes for all paths (e.g., `200` OK, `400` Bad Request, `401` Unauthorized, `502` Bad Gateway).
- [ ] **Error Details:** Fails gracefully with actionable error feedback in JSON format without leaking raw sensitive credentials.

### C. Security & Credentials
- [ ] **Environment Resolution:** Secrets and keys are retrieved dynamically via environment helpers (`get_env()` or `$_ENV`) rather than hardcoded strings.
- [ ] **File Permissions:** Any local storage operations (e.g., token files) enforce restrictive permissions (`0600`) and handle directory creation failure.

### D. Mocking & Diagnostic Safeguards
- [ ] **Mock Flagging:** Dev placeholders explicitly declare `'environment' => 'test'` or `'mock' => true` in their output payload.
- [ ] **Clean Output:** Temporary debug statements (`error_log`, dump routines) are stripped or gated behind dev flags before merge.

---

## 4. Testability & Critical File Identification

Introducing unit and integration tests acts as a primary tool for identifying project-critical files:

- **Dependency Friction Signal:** If a controller is difficult to test due to global variables, direct cURL execution, or filesystem dependencies, it is a **critical workhorse** requiring service extraction.
- **Contract Tests:** Minimal test suites should verify:
  1. JSON response key structure.
  2. HTTP status codes on missing or invalid parameters.
  3. Graceful handling of unconfigured credentials.
- **Service Boundary Trigger:** If a controller cannot be tested without live credentials or network access, it should be refactored behind a service boundary.

---

## 5. Review Order for Demo Target

To align with the upcoming demo build, complete controller reviews in the following sequence:

1. **Standalone Cloud Controllers:** `GoogleapiController`, `HotmailapiController`, `AwsapiController`, `AzureapiController`
2. **Framework Core & Routing:** `TestController`, `TerminalController`
3. **Agent & Pipeline Endpoints:** Inbound SMS webhooks, document generation, and RAG/vector orchestration components.

# Controller Code Review & Response Contract Audit

## Overview & Architectural Principles
The standard response boundary across all application controllers must route through `BaseController::json()`. Direct JSON outputs, manual `echo` statements, or raw `header()` modifications are treated as code smells unless explicitly justified.

### Architectural Takeaways
* **`BaseController::json()` as Standard:** Centralized response handler ensuring consistent HTTP status codes and `Content-Type: application/json; charset=utf-8` headers.
* **Predictable Boundaries:** Operational controllers already utilize JSON as their primary boundary, aiding testability and keeping HTTP interactions predictable.
* **Candidate Normalization:** Domain and data controllers act as thin pass-throughs between models and `$this->json()`, making them prime candidates for standardized response structures.

---

## Response Strategy & Review Implications

1. **Output Consolidation:** Any controller using direct `header()`, `echo`, or manual `json_encode()` calls must be refactored to use `$this->json()`.
2. **Single Responsibility Principle (SRP):** Controllers executing terminal commands, cURL requests, vector database queries, or job writes must be evaluated for SRP violations before normalizing response payloads.
3. **Strict Contracts:** Infrastructure, health, and monitoring endpoints require strict response contracts to ensure reliability for automated tooling and internal orchestration.

> **Rule:** If a controller returns data to the client or an external service, it must return through `BaseController::json()` unless a specific exception is formally documented.

---

## Controller Audit Matrix

| Controller | JSON Helper Used | Direct JSON Usage | External I/O | Status Code Consistency | Refactor Priority |
| :--- | :---: | :---: | :--- | :---: | :---: |
| **`BaseController`** | Native | N/A | None | High | Low (Source) |
| **`IngestionController`** | Yes | Minimal | Filesystem / DB / HTTP | Moderate | Medium |
| **`JobController`** | Yes | Minimal | Filesystem / Vector Store | Moderate | High |
| **`HealthController`** | Yes | No | DB / LLM / External APIs | High | Low |
| **`ScaffoldController`** | Yes | No | Database Schema | High | Low |
| **`BashController`** | Yes | No | Terminal / OS Execution | High | Medium |
| **`SearchController`** | Yes | No | Vector DB / Neural Embeddings | High | Low |
| **`VectorController`** | Yes | Low | Vector DB / Filesystem | Moderate | Medium |
| **`RagController`** | Yes | No | Vector DB / AI Engine | High | Low |
| **`ChatController`** | Yes | No | AI Services | High | Low |
| **`EmailsController`** | Yes | Low | Queue Storage / Local IO | Moderate | Medium |
| **`SmsController`** | Yes | Low | Queue Storage / Local IO | Moderate | Medium |
| **`GoogleController`** | Yes | No | OAuth / External APIs | High | Low |
| **`GoogleapiController`** | Yes | No | Google API Services | High | Low |
| **`HotmailapiController`** | Yes | No | Microsoft Graph APIs | High | Low |
| **`AzureapiController`** | Yes | No | Azure Cloud APIs | High | Low |
| **`DocsController`** | Yes | Potential | Database | Moderate | Low |
| **`QueryController`** | Yes | Potential | Database | Moderate | Low |
| **`LeadController`** | Yes | Potential | Database | Moderate | Low |
| **`SalesController`** | Yes | Potential | Database | Moderate | Low |
| **`FacebookController`** | Yes | Potential | External API / Database | Moderate | Medium |

---

## Priority Action Items

* **High Priority (`JobController`):** Manages multiple direct write operations and embedding generation alongside response outputs. Ensure strict status code propagation to prevent unhandled queue failures.
* **Medium Priority (`IngestionController`, `VectorController`, `EmailsController`, `SmsController`):** Manages file imports, queue drops, and database chunk syncing. Audit to eliminate any remaining direct `echo json_encode(...)` calls in favor of `$this->json()`.
* **Low Priority (API & Domain Controllers):** Thin wrappers over vendor APIs or simple model queries that largely comply with the base controller's response contract.