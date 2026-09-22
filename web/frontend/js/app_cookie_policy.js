App.cookiePolicy = function() {
    return `
        <div id="cookie" class="cookie-panel">
            <div class="cookie-header">
                <span class="cookie-icon">⚡</span>
                <span class="cookie-title">DATA_TELEMETRY_CONSENT</span>
                <span class="cookie-status">SYS_REQD</span>
            </div>
            
            <div class="cookie-body">
                <p class="cookie-text">
                    This platform uses essential cookies and local neural state storage to optimize pipeline processing, persist interface state, and enable seamless routing across nodes.
                </p>

                <div class="cookie-tags">
                    <span class="cookie-tag active">SESSION_STATE</span>
                    <span class="cookie-tag active">ROUTING_PREFS</span>
                    <span class="cookie-tag optional">ANALYTICS_TELEMETRY</span>
                </div>
            </div>

            <div class="cookie-footer">
                <button id="btn-reject" class="btn-cookie btn-cookie-secondary">DECLINE</button>
                <button id="btn-accept" class="btn-cookie btn-cookie-primary">INITIALIZE_SESSION</button>
            </div>
        </div>
    `;
};