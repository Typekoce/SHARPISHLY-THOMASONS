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
