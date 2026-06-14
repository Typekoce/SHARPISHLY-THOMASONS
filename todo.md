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

