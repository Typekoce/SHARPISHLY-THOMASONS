# Python Execution Layer
import playwright.sync_api

def run_automation(payload):
    target_url = payload['target_url']
    field_map = payload['field_map'] # e.g., {'#name_input': 'John Doe'}
    
    with playwright.sync_api.sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        page.goto(target_url)
        
        # Deterministic mapping: No AI guessing
        for selector, value in field_map.items():
            page.fill(selector, value)
            
        if payload.get('submit_policy') == 'auto':
            page.click('button[type="submit"]')
            
        browser.close()
