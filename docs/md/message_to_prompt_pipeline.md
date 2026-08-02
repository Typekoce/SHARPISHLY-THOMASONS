# Message-to-Prompt Execution Pipeline

## Overview
The Message-to-Prompt feature converts raw, unstructured user text instructions entered from the mobile client into structured, executable agent plans. This architecture processes multi-intent inputs across platform services without relying on worker scripts or raw SQL.

---

## 1. Request Flow

1. **User Input (`mobile_controller.js`)**
   - User inputs natural language instructions into `#agent-instruction`.
   - `MobileController.generateAgent()` sends a `POST` request payload to the `/mobile-agent` endpoint.

2. **Controller Layer (`MobileAgentController.php`)**
   - Validates incoming JSON payload.
   - Delegates prompt deconstruction to `AgentPlanner`.
   - Returns a structured status response back to the client.

3. **Data Layer (`AgentModel.php`)**
   - Saves the generated agent plan into the database via `$this->db->save()`.
   - Provides atomic record claiming using `claimNextPending()` via native model abstractions (`$this->db->find()`).

---

## 2. Multi-Intent Deconstruction Example

### Raw User Input
> "Send emails to my work colleagues only, update calendar for up coming holidays that fall on any birthday, update Facebook posts"

### Deconstructed Job Schema (`AgentPlanner`)
```json
{
  "title": "Multi-Task Operations Pipeline",
  "category": "workspace",
  "status": "pending",
  "tasks": [
    {
      "step": 1,
      "service": "email",
      "action": "send_email",
      "filters": {
        "recipient_group": "work_colleagues_only"
      }
    },
    {
      "step": 2,
      "service": "calendar",
      "action": "update_calendar",
      "rule": "match_upcoming_holidays_with_birthdays"
    },
    {
      "step": 3,
      "service": "facebook",
      "action": "update_posts"
    }
  ]
}
