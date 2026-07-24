### **Active Project TODO.md**

#### **1. Job Applications & Auto-Form Completion Targets**

* [ ] **ApplyBe Integration:** Track progress via [ApplyBe](https://www.google.com/search?q=https://www.applybe.com/%3Fa%3D145F80311.0).
* [ ] **NHS Public Wales:** Track application for [NHS Public Wales Job Advert C9028-26-0086](https://www.google.com/search?q=https://beta.jobs.nhs.uk/candidate/jobadvert/C9028-26-0086).
* [ ] **Defra Lead Software Developer:** Track application for the [Civil Service Jobs posting](https://www.google.com/search?q=https://www.civilservicejobs.service.gov.uk/csr/index.cgi%3FSID%3Db3duZXJ0eXBlPWZhaXImc2VhcmNocGFnZT0xJnVzZXJzZWFyY2hjb250ZXh0PTE5MTE2NDU5NSZqb2JsaXN0X3ZpZXdfdmFjPTE5OTk0ODgmb3duZXI9NTA3MDAwMCZzZWFyY2hzb3J0PXNjb3JlJnBhZ2VhY3Rpb249dmlld3ZhY2J5am9ibGlzdCZwYWdlY2xhc3M9Sm9icyZyZXFzaWc9MTc4MDA2OTM1Ni1lMDkyMzExMDY1ZmYxYTI0YTVmZDE0NGQwNjhjZWRkYjY2Njc4MjZm).
* [ ] **Cookie Banner Handling:** Implement pop-up detection to accept cookies automatically during form ingestion.
* [ ] **AWD Online:** Track application for [AWD Online Full Stack Software Developer](https://www.google.com/search?q=https://www.awdo.co.uk/jobs/software-developer-full-stack/11564-1/).

#### **2. Intelligent Ingestion Pipeline & Routing**

* [ ] **Routing Validation (High):** Ensure the `IngestionController::save()` method is correctly mapped and accessible via direct route resolution or messaging brokers.
* [ ] **Integration Testing (High):** Perform a full end-to-end test of form submissions using the server-side auto-fill and determine if a headless browser bridge is needed for active JS/AJAX target sites.
* [ ] **UI/UX Styling (Medium):** Apply `#leadForm` styles from `agents.css` to the ingestion preview for dashboard consistency.
* [ ] **MVC/DRY Cleanup (Low):** Audit `IngestionModel` and `IngestionController` to prevent responsibility leakage and centralize DOM manipulation.
* [ ] **Testability (Low):** Ensure `populateForm` can be unit-tested by passing a mock `DOMDocument` without requiring live internet access.
* [ ] **Phase 1 (LLM Mapping):** Create `IngestionClient` service in `App/Services` to interface with the Python LLM layer and return field-mapping JSON.
* [ ] **Phase 2 (RAG Integration):** Create `KnowledgeRetriever` service to fetch snippets from the Qdrant vector database via the Python layer, update prompt context construction, and handle longer text blocks for `textarea` fields.
* [ ] **Phase 3 (Agentic Orchestration):** Implement a state machine for ingestion to monitor success/failure, handle validation error loops, and re-submit corrected data to the LLM.

#### **3. Critical Stability & Bug Fixes (High Priority)**

* [ ] **Fix 500 Error on Google Auth:** Diagnose and resolve the `500 Internal Server Error` triggered on `/php/google/auth` by reviewing Nginx/PHP error logs.
* [ ] **Restore Agent Form Save Functionality:** Debug and fix the broken form save behavior in the ingestion/agent workflow.
* [ ] **Resolve "Failed to fetch preview":** Investigate client-side/server-side fetch failures blocking `autoform`.

#### **4. Azure AI Foundry & Cloud Integration Setup**

* [ ] **Portal Reference Update:** Access the correct portal URL at `[https://ai.azure.com](https://ai.azure.com)` and sign in.
* [ ] **Hub-Based Deployment Configuration:** Create a hub-based project first, then deploy models (such as `gpt-4o-mini`) as a Serverless API from the Model catalog.
* [ ] **Target URI Extraction & Configuration:** Open deployment details under `Models + endpoints` and copy the **Target URI and API key exactly as shown** (avoiding hardcoded generic URI templates).
* [ ] **Local Secret Management via `env.php`:** Store the exact portal-provided Target URI and API key inside the local, untracked `env.php` file, passing the API key via the `api-key` header.
* [ ] **Cloud & Domain Environments:** Configure AWS, DigitalOcean, GoDaddy, and Zoho integrations.

#### **5. Infrastructure, Local SSL & Repository Sync**

* [ ] **Local SSL & Host Troubleshooting:**
* [ ] Complete local SSL setup on host `@seaview`.
* [ ] Resolve browser connection issues for `[https://sharpishly.dev/](https://sharpishly.dev/)` on host `@foozie`.


* [ ] **Bitbucket Synchronization:** Generate and configure SSH keys to successfully push the current `SHARPISHLY-THOMASONS` repository to Bitbucket.
* [ ] **Image Asset Management:** Resize the schema image `sharpishly-schema.png`.
* [ ] **Vector Storage Service Cleanup:** Create `env.py` for Python services, migrate `PERSIST_PATH` and `GLOBAL_COLLECTION` constants, and test the RAG retrieval endpoint.

#### **6. Feature, UI Refinement & Page Registry**

* [ ] **Update Page Registry:** Uncomment and register the designated action elements in the frontend registry:
* `{id: 'autoform', name: 'Automatic Form Completion'}`
* `{id: 'snapshot', name: 'Scrape'}`
* `{id: 'tiktok', name: 'Tiktok'}`
* `{id: 'pentest', name: 'Penetration Testing'}`
* `{id: 'pension', name: 'Pension Schemes'}`
* `{id: 'morrisons', name: 'Morrisons Staff community shop'}`


* [ ] **UI Navigation & Helpers:**
* [ ] Implement "Form Reset" button for the `autoform` view.
* [ ] Implement "Back" button functionality for agent action views.
* [ ] Add Breadcrumbs for navigation across agent modules.
* [ ] Create placeholder views for unbuilt agent actions to prevent routing errors.
* [ ] Add a pop-up modal confirmation message when a user attempts to close the page.


* [ ] **JavaScript Safeguards:** Introduce validation or naming checks to standardize `App = {}` and prevent casing mismatch errors.

#### **7. Backend Services & RAG Enhancements**

* [ ] **RAG Chat Logging:** Implement logic in `RagController` to automatically log successful Q&A chat exchanges (query + answer) to the `queries` database table.
* [ ] **SalesForce Controller Polish:** Refine the basic SalesForce controller to prepare it for secure integration testing.
* [ ] **Federation of Small Business (FSB) Integration:** Map out initial automation workflows for FSB interaction as part of the launch strategy.