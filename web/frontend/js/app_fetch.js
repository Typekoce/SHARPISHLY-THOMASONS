App.api = async function(url, options = {}) {
    // Standardize GET query parameters vs request body
    const method = (options.method || 'GET').toUpperCase();
    let endpoint = App.url ? App.url(url) : url;

    const config = {
        method: method,
        headers: {
            'Accept': 'application/json',
            ...(options.headers || {})
        }
    };

    if (method === 'GET' && options.data) {
        const params = new URLSearchParams(options.data).toString();
        endpoint += (endpoint.includes('?') ? '&' : '?') + params;
    } else if (options.data) {
        config.headers['Content-Type'] = 'application/json';
        config.body = JSON.stringify(options.data);
    }

    const response = await fetch(endpoint, config);

    if (!response.ok) {
        throw new Error(`API Error [${response.status}]: ${response.statusText}`);
    }

    return await response.json();
};