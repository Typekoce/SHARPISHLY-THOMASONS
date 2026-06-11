#!/bin/bash
# project_consolidation.sh: Finalize consolidation.

echo "🧹 Starting Project Consolidation..."

# 1. Remove redundant worker files
echo "--- Removing redundant worker files ---"
rm -f pymvc/app/worker.py
rm -f pymvc/app/email_worker.py

# 2. Ensure service directory exists
mkdir -p pymvc/app/services

# 3. Create AutomationService
cat <<EOF > pymvc/app/services/AutomationService.py
import playwright.sync_api

class AutomationService:
    @staticmethod
    def run(payload):
        target_url = payload.get('target_url')
        field_map = payload.get('field_map', {})
        mode = payload.get('mode', 'draft')
        with playwright.sync_api.sync_playwright() as p:
            browser = p.chromium.launch(headless=True)
            page = browser.new_page()
            page.goto(target_url)
            for selector, value in field_map.items():
                page.fill(selector, value)
            if mode == 'submit':
                page.click('button[type="submit"]')
                status = "submitted"
            else:
                page.screenshot(path=payload.get('artifacts', {}).get('screenshot_path', 'draft.png'))
                status = "draft_ready"
            browser.close()
            return {"status": status}
EOF

# 4. Create EmailService
cat <<EOF > pymvc/app/services/EmailService.py
import requests
import logging

class EmailService:
    PHP_ENDPOINT = "http://localhost/php/letterbox/"
    @staticmethod
    def send(job_data):
        job_id = job_data.get('job_id')
        try:
            response = requests.get(f"{EmailService.PHP_ENDPOINT}{job_id}", timeout=10)
            return {"status": "success"} if response.status_code == 200 else {"status": "failed"}
        except Exception as e:
            return {"status": "error", "message": str(e)}
EOF

echo "✅ AutomationService and EmailService created. Redundant workers removed."
