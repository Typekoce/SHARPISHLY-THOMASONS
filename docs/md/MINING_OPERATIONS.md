Mining Operations Platform: Operating Guide
1. Architecture Overview
Source of Truth: Odoo (System of record for operational and financial data).

Orchestration Layer: Custom service-oriented framework (ensuring clean separation between ERP fetch, AI analysis, and MS Graph sync).

Verification Logic: Document-to-insight mapping ensuring all AI outputs are traceable to a specific source document ID.

2. Connector Inventory
Odoo Connector: XML-RPC/REST abstractions for financial and mining operational modules.

Anthropic Agent: Standardized prompts for document taxonomy and financial data extraction.

Graph API Bridge: Webhook-based integration for SharePoint document management and Power Automate triggering.

3. Governance & Access Control
Least-Privilege Policy: Internal service-level authentication for all ERP-to-AI communication.

Audit Trail: Every agentic action logs the source record ID and the timestamped analysis output for executive review.

Change Management: Versioned automation catalog allowing for rollback of ERP model configurations.