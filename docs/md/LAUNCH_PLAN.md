# Launch Plan: Functional Website Release

This document outlines the core objectives, feature mappings, action phases, and acceptance criteria required to achieve a live, production-ready website with a seamless user experience.

---

## Core Launch Objectives

* **Objective 1: Core Platform Stability & Auth**
  Ensure rock-solid execution, error handling, and secure authentication.
* **Objective 2: Core MVC & Feature Completion**
  Deliver seamless user workflows across Notes, Todos, and Ingestion.
* **Objective 3: API & Service Pipeline Reliability**
  Secure background queues, command execution, and LLM/API integration layers.
* **Objective 4: Production Polish & Responsive UI**
  Refine mobile/desktop views, front-end interactions, and interface feedback.

---

## Tasks by Objective

### Objective 1: Core Platform Stability & Auth
* Resolve `500 Internal Server Error` on `/php/google/auth`.
* Fix `AgentExecuteController` input processing to handle empty JSON payloads (`php://input: {}`) and prevent validation crashes.
* Audit error logging and route fallbacks across `BaseController` to ensure full testability and graceful failure states.

### Objective 2: Core MVC & Feature Completion
* Fix Note Link controller initialization (Task #66) so clicking a Todo task link triggers `NotesController.init()` and renders existing content properly.
* Complete Todo Details page, status editing, and task alert descriptive data (Tasks #35, #57, #58, #59, #63).
* Complete Notifications MVC implementation (Task #41).
* Finalize inbound form automation, verifying `IngestionController::save()` mapping and end-to-end form submissions.

### Objective 3: API & Service Pipeline Reliability
* Fix command execution enqueueing in `TerminalController::load()` to reliably queue `.sh` jobs in `storage/cmd/jobs/waiting/`.
* Align model tag references across `Orm.php`, `BaseController`, and `DhillonsController` to prevent model query mismatches.
* Construct `IngestionClient` to bridge PHP controllers with external integration endpoints.
* Finalize cloud service integration updates and deploy the live `sharpishly-azure-hello` endpoint.

### Objective 4: Production Polish & Responsive UI
* Connect `DhillonsController` directly to the front-end interface for real-time user interaction.
* Complete mobile demo page layout: hide header banner, integrate RAG and RUN buttons, and close debug windows (Tasks #60, #61).
* Standardize UI notification alerts and loading indicators across all active views.

---

## Prioritized Action Plan

### Phase 1: Critical System Blockers (Days 1–2)
1. **Auth:** Fix 500 error on `/php/google/auth`.
2. **`AgentExecuteController`:** Harden payload handling for empty or missing JSON.
3. **Note Link (Task #66):** Ensure cross-linking between Todos and Notes works without manual view creation.
4. **Terminal Queueing:** Ensure `.sh` job files are written correctly to `storage/cmd/jobs/waiting/`.

### Phase 2: Essential Feature Completion (Days 3–4)
5. **Todo & Notifications MVCs:** Finish task editing, detail views, and real-time alert dispatching.
6. **Form Ingestion Pipeline:** Verify `IngestionController::save()` and connect `IngestionClient` for seamless data ingestion.
7. **Model Tag Standardization:** Fix tag mismatches in `Orm.php` and `DhillonsController` so background queries return valid data.

### Phase 3: Front-End Refinement & Production Launch (Day 5)
8. **UI & Mobile Clean-up:** Hide debug panels, optimize mobile controls (RAG/RUN buttons), and clean up top-level banners.
9. **Final Live Deployment:** Verify the `sharpishly-azure-hello` endpoint and run final end-to-end sanity checks across all primary routes.

---

## Targeted Implementation Guidelines

### 1. Acceptance Criteria ("Done" Definitions)
* **Auth:** `/php/google/auth` returns `302`/`200` for valid credentials and gracefully logs errors without raising a `500` error on invalid payloads.
* **Task Linking:** Clicking a Todo title link loads the Notes page with the correct record rendered without manual URL entry.
* **Terminal Queueing:** Invoking system tasks generates a valid shell executable script in `storage/cmd/jobs/waiting/`.

### 2. Smoke-Test Checklist
* [ ] **Auth Flow:** Verify login, session handling, and logout behavior.
* [ ] **Notes CRUD:** Create, read single, read list, update, and delete entries cleanly.
* [ ] **Todos CRUD:** Verify task status updates and note cross-linking.
* [ ] **Ingestion Pipeline:** Form submission produces a database entry via `IngestionController::save()`.
* [ ] **Terminal Queue:** Shell job creation places files correctly in `storage/cmd/jobs/waiting/`.
* [ ] **Mobile Layout:** Confirm banner removal, functional RAG/RUN buttons, and hidden debug panels.

### 3. Debug Flag Management
Ensure application environment configuration enforces debug toggles:
```php
if (defined('APP_DEBUG') && APP_DEBUG === false) {
    // Suppress #debug-output rendering
    // Minimize verbose console log output
}