def run_automation(payload):
    mode = payload['mode'] # "draft" or "submit"
    
    # 1. Setup Playwright page...
    
    # 2. Fill fields (Applies to both draft and submit)
    for selector, value in field_map.items():
        page.fill(selector, value)
        
    # 3. Mode logic
    if mode == 'draft':
        page.screenshot(path=artifacts['screenshot_path'])
        return {"status": "draft_ready"}
        
    if mode == 'submit':
        page.click('button[type="submit"]')
        return {"status": "submitted"}
