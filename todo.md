### TODO ###

## Auto-form completion

ApplyBe
[ApplyBe](https://www.applybe.com/?a=145F80311.0)

NHS PUBLIC WALES
[NHS PUBLIC WALES](https://beta.jobs.nhs.uk/candidate/jobadvert/C9028-26-0086)

[Lead Software Developer
Department for Environment, Food and Rural Affairs](https://www.civilservicejobs.service.gov.uk/csr/index.cgi?SID=b3duZXJ0eXBlPWZhaXImc2VhcmNocGFnZT0xJnVzZXJzZWFyY2hjb250ZXh0PTE5MTE2NDU5NSZqb2JsaXN0X3ZpZXdfdmFjPTE5OTk0ODgmb3duZXI9NTA3MDAwMCZzZWFyY2hzb3J0PXNjb3JlJnBhZ2VhY3Rpb249dmlld3ZhY2J5am9ibGlzdCZwYWdlY2xhc3M9Sm9icyZyZXFzaWc9MTc4MDA2OTM1Ni1lMDkyMzExMDY1ZmYxYTI0YTVmZDE0NGQwNjhjZWRkYjY2Njc4MjZm)

Pop-up to accept cookies

[awdonline Software Developer - Full Stack](https://www.awdo.co.uk/jobs/software-developer-full-stack/11564-1/)


### **Project Priority Checklist (Active TODO.md)**

* [ ] **1. Routing Validation (High):** Ensure the `IngestionController::save()` method is correctly mapped and accessible via your Nats-based messaging or direct route resolution.
* [ ] **2. Integration Testing (High):** Perform a full end-to-end test of the form submission. Since we have the server-side auto-fill working, confirm that submitting the external form works as expected (or determine if we need to bridge with a browser automation tool).
* [ ] **3. UI/UX Styling (Medium):** Apply the `#leadForm` styles from `agents.css` to the ingestion preview if you want the "transformed" page to look consistent with your dashboard.
* [ ] **4. MVC/DRY Cleanup (Low):** Audit the `IngestionModel` and `IngestionController` to ensure we aren't leaking any responsibilities and that all DOM manipulation is centralized.
* [ ] **5. Testability (Low):** Ensure the `populateForm` method can be unit-tested by passing a mock `DOMDocument` to confirm input injection without needing live internet access.

### Amended list

I understand—I will keep our project's tasks updated in my active memory as our virtual `TODO.md`. Based on our current progress and the architecture we've been building, here is your prioritized task list:

### **Project Priority Checklist (Active TODO.md)**

* [ ] **1. Routing Validation (High):** Ensure the `IngestionController::save()` method is correctly mapped and accessible via your Nats-based messaging or direct route resolution.
* [ ] **2. Integration Testing (High):** Perform a full end-to-end test of the form submission. Since we have the server-side auto-fill working, confirm that submitting the external form works as expected (or determine if we need to bridge with a browser automation tool).
* [ ] **3. UI/UX Styling (Medium):** Apply the `#leadForm` styles from `agents.css` to the ingestion preview if you want the "transformed" page to look consistent with your dashboard.
* [ ] **4. MVC/DRY Cleanup (Low):** Audit the `IngestionModel` and `IngestionController` to ensure we aren't leaking any responsibilities and that all DOM manipulation is centralized.
* [ ] **5. Testability (Low):** Ensure the `populateForm` method can be unit-tested by passing a mock `DOMDocument` to confirm input injection without needing live internet access.

### Additional HTML Ingestion

To integrate these features while maintaining your strict MVC and decoupled service architecture, I have broken down the tasks into logical, actionable steps for your `TODO.md`.

### **New TODO.md Section: Intelligent Ingestion Pipeline**

#### **Phase 1: LLM-Driven Mapping (Cognitive Layer)**

* [ ] **1. Create `IngestionClient` Service:** Build a service in `App/Services` that acts as the interface for your Python-based `LLM` layer. It should handle the request to the local AI service and return the field mapping JSON.
* [ ] **2. Update `IngestionController` Flow:** Modify the `save()` method to:
* Capture the HTML form.
* Pass the HTML to `IngestionClient` to generate an associative array mapping `name` to `contextual_value`.
* Inject the result into the DOM using your existing `populateForm()` method.



#### **Phase 2: RAG Integration (Knowledge Layer)**

* [ ] **3. Create `KnowledgeRetriever` Service:** Interface with your Qdrant vector database via the Python layer to fetch relevant snippets (e.g., "Project Experience", "Skills").
* [ ] **4. Prompt Context Construction:** Update the `IngestionController` to fetch relevant profile data via the `KnowledgeRetriever` before sending the request to the LLM.
* [ ] **5. Dynamic Content Injection:** Extend `IngestionModel::populateForm()` (or create a new `TemplateInjector` service) to handle longer text blocks for `textarea` fields, ensuring RAG-retrieved data is correctly formatted.

#### **Phase 3: Agentic Orchestration (Execution Layer)**

* [ ] **6. Implement State Machine for Ingestion:** Move beyond one-shot ingestion. Create a controller method that monitors submission success or failure messages.
* [ ] **7. Error Handling Loop:** Implement logic to parse "Validation Error" responses from the remote site, re-submit the data to the LLM for correction, and re-run the `populateForm()` injection.
* [ ] **8. Headless Bridge Integration:** If the target site requires active JS/AJAX, create a small bridge in the Python layer to handle the actual form submission, using the PHP side strictly for DOM preparation and payload management.

---

### **Integration Workflow Diagram**

**Which phase would you like to prioritize for the next coding session?** I recommend starting with the `IngestionClient` service in Phase 1, as it creates the essential communication bridge between your PHP Brain and the LLM Cognition layer.

### Vector Storage Service
# TODO List

- [ ] Create `env.py` to centralize environment and path configurations for Python services.
- [ ] Migrate `PERSIST_PATH` and `GLOBAL_COLLECTION` constants from `VectorStorageService.py` to `env.py`.
- [ ] Test the RAG retrieval endpoint with the verified path to ensure context is correctly fetched.