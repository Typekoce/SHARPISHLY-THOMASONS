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

# TODO: Fix Terminal Model & Command Queue Enqueueing Architecture

## Background / Issue Summary
Attempts to execute remote deployment and provisioning tasks via `curl -i http://localhost/php/terminal/load/<alias>` failed to execute on `@maxie` (`192.168.0.90`). 

While `TerminalController` logged the incoming HTTP requests, `TerminalModel::alias()` merely returns string mapping definitions. The endpoint was not creating `.sh` job files in `storage/cmd/jobs/waiting/`. Consequently, background workers never picked up or executed the commands (such as `maxie-keygen`), leading to missing SSH keys and stalled deployment tasks.

---

## Required Tasks

### 1. Refactor `TerminalController` & `TerminalModel`
- [ ] **Implement File Enqueueing Logic:** Update `TerminalController::load()` (or the appropriate action) so that resolving an alias generates an executable `job_<timestamp>_<hash>.sh` file inside `storage/cmd/jobs/waiting/`.
- [ ] **Validate File Permissions:** Ensure generated job files are written with executable permissions (`0755` or `0777`) so `start_workers.sh` can execute them without permission errors.
- [ ] **Enforce Queue Cleanliness:** Ensure failed or empty enqueues are handled gracefully without leaving orphan files.

### 2. Standardize Remote SSH & Host Authorization
- [ ] **Establish Host-Level Key Auth:** Run `ssh-copy-id maxie@192.168.0.90` on the host running the workers (`seaview`) to ensure background worker processes can connect to `@maxie` without interactive password prompts.
- [ ] **Enforce BatchMode:** Verify all `ssh` commands within `TerminalModel.php` aliases include `-o BatchMode=yes` to prevent non-interactive worker processes from hanging on password prompts.

### 3. End-to-End Verification Flow
- [ ] **Verify Queue Output:** Confirm that issuing a `curl` request to an alias endpoint writes a valid job file into `storage/cmd/jobs/waiting/`.
- [ ] **Verify Worker Execution:** Confirm that `start_workers.sh` processes the job file, moves it to `processing/`, executes it, and archives the output log in `completed/`.
- [ ] **Test `@maxie` Provisioning:** Test `maxie-keygen` and verify `id_ed25519.pub` is generated successfully on `@maxie`.

## Health Center & Diagnostics

- [ ] **Fix Ollama ORM Integration (`Orm.php`)**
  - Update model payload tag to `llama3:latest` to match local engine tags.
  - Verify endpoint URL targets `http://127.0.0.1:11434/api/generate` with `stream: false`.

- [ ] **Populate RAG Health Payload (`TestController.php`)**
  - Bind ping check to local RAG endpoint (`http://127.0.0.1:8000/health`) so `$data['RAG']` returns status instead of `null`.

- [ ] **Format Process Output in UI (`health_controller.js`)**
  - Update `renderTree()` to render multiline strings (like `process_check`) inside `<pre class="tree-val mb-0 text-wrap">` tags.

- [ ] **Background Supervisor Execution**
  - Verify `ingestion_worker.py` and `cmd_worker` are running via `./start_workers.sh`.

  # On the Mobile Demo page
  * Create a link that hides the header banner
  * Add RAG
  * Add RUN button

  # Back database to Google Drive

  # Header UU
  * Enable burger collapse, etc

  ### Active Bugs & Technical Debt (From Log Analysis)

- [ ] **Fix Empty Body Handling in `AgentExecuteController`**
  - **Issue:** `POST /php/agent-execute/start` is receiving an empty JSON payload (`php://input: {} | DATA: []`), causing `AgentExecuteController::start()` to fail payload validation and throw HTTP 400 errors.
  - **Task:** Verify payload format on client dispatch or update `AgentExecuteController` to handle fallback defaults when receiving `{}`.

- [ ] **Pull or Update Ollama Target Model (`llama3.1:latest`)**
  - **Issue:** Gateway queries to Dhillon's Brewery return synthesis error: `{"synthesis":{"error":"model 'llama3.1:latest' not found"}}`.
  - **Task:** Run `ollama pull llama3.1:latest` locally or update `REQUIRED_MODELS` / fallback logic in `BaseController` and `DhillonsController`.

- [ ] **Refactor or Terminate `ingestion_worker.py` Process**
  - **Issue:** Background worker continuously crashes with `ImportError: cannot import name 'IngestionController' from 'app.controllers.nats_controller'`.
  - **Task:** Fix module imports in `pymvc/app/ingestion_worker.py` or disable the supervisor daemon/service managing this process.

- [ ] **Investigate RAG Service Connection Dropouts (`BrokenPipeError`)**
  - **Issue:** Python HTTP server in `pymvc/app/rag_service.py` encounters `[Errno 32] Broken pipe` during client write.
  - **Task:** Increase cURL timeout limits in `BaseController::respond()` or handle socket write disconnects cleanly in `rag_service.py`.

# Wire up DhillonsController.php to the front-end
* Create new icon in mobile phone screen
* Deploy this project ASAP

#### **Diagnostics & Gateway Stability**
- [x] **Integrate `Diagnostics` in `BaseController`:** Instantiate `App\Services\Diagnostics` in `BaseController` to capture file names, line numbers, exception class types, and stack traces on failures.
- [ ] **Expose Detailed Error Payloads:** Replace opaque error strings (`request_failed`) across gateway endpoints with detailed diagnostic traces for rapid debugging.
- [ ] **Verify Ollama Model Tag:** Ensure Ollama gateway calls reference active local model tags (`llama3:latest` / `llama3.1:latest`).

# Deploy to @maxie
* React Native, Expo, NPM, Node, Tailwind, Docker packe
* PureShare via Docker
* Use TerminalModel.php
```
 'react-native' => 'docker compose...'
```

# Security for TerminalController.php

```
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\TerminalModel;

class TerminalController extends BaseController
{
    private TerminalModel $terminalModel;

    /**
     * Strict whitelist of safe diagnostic commands permitted for HTTP execution.
     */
    private const ALLOWED_HTTP_ALIASES = [
        'status',
        'git-log',
        'health-controller',
        'test-controller',
        'schema-check',
        'nginx-header-check'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->terminalModel = new TerminalModel();
    }

    /**
     * Executes whitelisted terminal aliases and returns JSON output.
     */
    public function execute(): void
    {
        $command = $this->request('cmd');

        if (!$command || !in_array($command, self::ALLOWED_HTTP_ALIASES, true)) {
            $this->json([
                'status'  => 'error',
                'message' => 'Unauthorized or restricted terminal command.'
            ], 400);
        }

        $execString = $this->terminalModel->alias($command);

        if (!$execString) {
            $this->json([
                'status'  => 'error',
                'message' => 'Command alias mapping failed.'
            ], 404);
        }

        $output = shell_exec($execString . ' 2>&1');

        $this->json([
            'status'  => 'success',
            'command' => $command,
            'output'  => trim((string)$output)
        ]);
    }
}
```
- [ ] **Azure Functions Setup & Infra Verification**
  - **Resource Group Location Constraint:** `rg-sharpishly` is bound to `uksouth`. Ensure all CLI commands target `uksouth` for the resource group or use a new RG name (e.g., `rg-sharpishly-ne`) if deploying to `northeurope`.
  - **Namespace Registration:** Register the `Microsoft.CloudShell` resource provider on subscription `09e3bedf-2add-4a8a-917e-27e642ba8660` via `az provider register --namespace Microsoft.CloudShell`.
  - **Node Runtime:** Deploy using Node.js version 24 (`--runtime-version 24`), as Node 20 reached EOL on 2026-04-30.
  - **Deployment Target:** Finalize deployment of `sharpishly-azure-hello` endpoint and map URL inside `App/Services/Orm.php` for integration testing.
