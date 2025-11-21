const API_BASE = '/api';

const Api = {
    async _request(endpoint, method, body = null, isFile = false) {
        const options = {
            method: method,
            headers: {},
            credentials: 'include' 
        };

        if (!isFile) {
            options.headers['Content-Type'] = 'application/json';
            if (body) options.body = JSON.stringify(body);
        } else {
            // Jika upload file (FormData), jangan set Content-Type (browser otomatis set)
            options.body = body;
        }

        try {
            const res = await fetch(`${API_BASE}${endpoint}`, options);
            const json = await res.json();
            
            // Handle error HTTP (400, 500, dll)
            if (!res.ok) {
                throw new Error(json.message || `HTTP Error ${res.status}`);
            }
            
            return json;
        } catch (error) {
            console.error('API Error:', error);
            throw error; 
        }
    },

    get: (endpoint) => Api._request(endpoint, 'GET'),
    post: (endpoint, body) => Api._request(endpoint, 'POST', body),
    put: (endpoint, body) => Api._request(endpoint, 'PUT', body),
    delete: (endpoint, body) => Api._request(endpoint, 'DELETE', body),
    upload: (endpoint, formData) => Api._request(endpoint, 'POST', formData, true)
};