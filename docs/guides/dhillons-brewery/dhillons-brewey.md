# Dhillon's Brewery — Multi-System Integration Gateway

This documentation details the architecture, request structure, and extension model for the **Dhillon's Brewery Integration Gateway** built within the Sharpishly PHP engine.

---

## Architectural Overview

The gateway provides a single unified natural language API interface that connects Dhillon's Brewery operational systems—including POS sales, table reservations, live event ticketing, project management, and venue calendars—without third-party vendor dependencies or heavy background queues.

```
┌────────────────────────────────────────────────────────┐
│               Client Request (POST /query)             │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
               ┌──────────────────────────┐
               │    DhillonsController    │
               └────────────┬─────────────┘
                            │
       ┌────────────────────┼────────────────────┐
       ▼                    ▼                    ▼
┌──────────────┐   ┌─────────────────┐  ┌──────────────────┐
│BaseController│   │  PromptService  │  │ fetchSystemData  │
│ Request/DB   │   │   (RAG / NLP)   │  │   (cURL Multi)   │
└──────────────┘   └─────────────────┘  └────────┬─────────┘
                                                 │
                     ┌───────────────────────────┴───────────────────────────┐
                     ▼                           ▼                           ▼
            ┌─────────────────┐         ┌─────────────────┐         ┌─────────────────┐
            │   Square POS    │         │    OpenTable    │         │    Eventbrite   │
            └─────────────────┘         └─────────────────┘         └─────────────────┘
                     │                           │                           │
                     └───────────────────────────┼───────────────────────────┘
                                                 │
                                                 ▼
                                        ┌─────────────────┐
                                        │   Orm / Ollama  │
                                        │ (llama3.1:latest)│
                                        └─────────────────┘

```

### Key Technical Characteristics

* **Thin Controller Architecture:** Inherits directly from `BaseController` for request parsing (`$this->request()`), database logging (`$this->db->save()`), timestamping (`$this->timestamp()`), error logging (`$this->logger`), and JSON response handling (`$this->json()`).
* **Non-Blocking cURL Concurrency:** Uses PHP `curl_multi_*` socket polling with `CURLM_CALL_MULTI_PERFORM` loop mechanics to execute parallel HTTP calls across all venue systems simultaneously.
* **Semantic Enrichment:** Leverages `PromptService::promptToConditions()` to parse queries locally and enrich context via the vector RAG service prior to model synthesis.
* **Resilient Parsing:** Handles non-JSON API outputs (such as HTML error pages or gateway timeouts) gracefully by preserving raw string payloads rather than crashing the pipeline.

---

## Endpoint Reference

### `POST /php/dhillons/query`

Executes multi-system queries across sales, bookings, event calendars, and forecasting data.

#### Request Headers

* **Content-Type:** `application/json`

#### Request Payload

```json
{
  "prompt": "What were our sales last week across each venue, how does that compare to the previous week, and do we have any upcoming bookings that could affect this week's forecast?"
}

```

*Note: If no `prompt` key is provided, the controller defaults to the standard operational query shown above.*

---

## Example Response

```json
{
  "status": "completed",
  "company": "Dhillon's Brewery",
  "prompt": "What were our sales last week across each venue, how does that compare to the previous week, and do we have any upcoming bookings that could affect this week's forecast?",
  "conditions": {
    "status": "pending",
    "action": "GENERIC_QUERY",
    "rag_context": "Venue Context: Brewery Taproom (CV6), Sky Blue Tavern, Dhillon's Lounge CBS Arena."
  },
  "synthesis": {
    "status": "success",
    "output": "Sales across the Brewery Taproom and Sky Blue Tavern totaled £14,250 last week, representing a 12% increase compared to the prior week. OpenTable and Eventbrite data indicate 3 upcoming private hires and match-day events at CBS Arena that are projected to increase taproom demand by 25%."
  }
}

```

---

## Extending System Endpoints

To integrate additional venue platforms (e.g., Shopify, Xero, or Mailchimp):

1. **Add the URL to `$systemEndpoints`:**
```php
private array $systemEndpoints = [
    'square'     => 'https://connect.squareup.com/v2/reports/sales',
    'opentable'  => 'https://api.opentable.com/v2/bookings',
    'eventbrite' => 'https://www.eventbriteapi.com/v3/organizations/me/events/',
    'clickup'    => 'https://api.clickup.com/api/v2/team',
    'google'     => 'https://www.googleapis.com/calendar/v3/calendars/primary/events',
    'shopify'    => 'https://dhillonsbrewery.myshopify.com/admin/api/2026-04/orders.json',
];

```


2. **Add Header Authentication inside `fetchSystemData()` (if required):**
```php
$headers = ['Accept: application/json'];

if ($key === 'square') {
    $headers[] = 'Authorization: Bearer ' . getenv('SQUARE_ACCESS_TOKEN');
} elseif ($key === 'shopify') {
    $headers[] = 'X-Shopify-Access-Token: ' . getenv('SHOPIFY_API_KEY');
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

```



---

## Testing & CLI Verification

You can execute diagnostic queries directly against the controller endpoint via standard command line tools:

```bash
curl -k -X POST http://localhost/php/dhillons/query \
     -H "Content-Type: application/json" \
     -d '{
       "prompt": "Compare Taproom vs Sky Blue Tavern sales for the last 7 days."
     }'

```