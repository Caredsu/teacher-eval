/**
 * API Redirect Fix
 * Intercepts hardcoded IP-based API calls and redirects to relative paths
 * Must be loaded BEFORE Flutter app initializes
 */

(function() {
    const HARDCODED_IPS = ['192.168.8.33', '127.0.0.1', 'localhost'];
    const API_BASE = window.location.pathname.split('/').slice(0, -1).join('/'); // /teacher-eval
    
    // Store original fetch
    const originalFetch = window.fetch;
    
    // Override fetch globally
    window.fetch = function(resource, config) {
        let url = typeof resource === 'string' ? resource : resource.url;
        
        // Check if this is a hardcoded IP request
        for (const ip of HARDCODED_IPS) {
            if (url.includes(`http://${ip}/teacher-eval`) || url.includes(`https://${ip}/teacher-eval`)) {
                // Extract the API path
                const apiPath = url.replace(/https?:\/\/[^/]+\/teacher-eval/, '');
                const newUrl = API_BASE + apiPath;
                
                console.log('🔄 API Redirect:', {
                    from: url,
                    to: newUrl
                });
                
                // Replace the URL
                if (typeof resource === 'string') {
                    return originalFetch.call(window, newUrl, config);
                } else {
                    resource.url = newUrl;
                    return originalFetch.call(window, resource, config);
                }
            }
        }
        
        // No redirect needed
        return originalFetch.call(window, resource, config);
    };
    
    // Copy over any properties from original fetch
    Object.setPrototypeOf(window.fetch, originalFetch);
    
    console.log('✅ API Redirect initialized. Base path:', API_BASE);
})();
