/**
 * API Redirect Fix + Teacher Filtering
 * 1. Intercepts hardcoded IP-based API calls and redirects to relative paths
 * 2. Filters out already-evaluated teachers from the teachers list
 * Must be loaded BEFORE Flutter app initializes
 */

(function() {
    const HARDCODED_IPS = ['192.168.8.33', '127.0.0.1', 'localhost'];
    const API_BASE = window.location.pathname.split('/').slice(0, -1).join('/'); // /teacher-eval
    
    // Store original fetch
    const originalFetch = window.fetch;
    
    /**
     * Get evaluated teacher IDs from localStorage
     */
    function getEvaluatedTeacherIds() {
        try {
            const submitted = localStorage.getItem('teacher_eval_submitted');
            if (!submitted) return [];
            
            const data = JSON.parse(submitted);
            return Object.keys(data);
        } catch (e) {
            console.error('Error reading evaluated teachers:', e);
            return [];
        }
    }
    
    /**
     * Add evaluated teacher IDs to teacher requests
     */
    function addTeacherFilter(url) {
        // Only filter teachers API calls (not specific teacher by ID)
        if (!url.includes('/api/teachers') || url.includes('/teachers/')) {
            return url;
        }
        
        const evaluatedIds = getEvaluatedTeacherIds();
        if (evaluatedIds.length === 0) return url;
        
        const separator = url.includes('?') ? '&' : '?';
        const filterParam = `evaluated_ids=${evaluatedIds.join(',')}`;
        const newUrl = url + separator + filterParam;
        
        console.log('📚 Teacher Status Filter Applied:', {
            evaluatedCount: evaluatedIds.length,
            teachers: evaluatedIds
        });
        
        return newUrl;
    }
    
    // Override fetch globally
    window.fetch = function(resource, config) {
        let url = typeof resource === 'string' ? resource : resource.url;
        
        // Check if this is a hardcoded IP request
        for (const ip of HARDCODED_IPS) {
            if (url.includes(`http://${ip}/teacher-eval`) || url.includes(`https://${ip}/teacher-eval`)) {
                // Extract the API path
                const apiPath = url.replace(/https?:\/\/[^/]+\/teacher-eval/, '');
                let newUrl = API_BASE + apiPath;
                
                // Add teacher filter if applicable
                newUrl = addTeacherFilter(newUrl);
                
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
        
        // No IP redirect needed - but still apply teacher filter if applicable
        if (typeof resource === 'string') {
            const filteredUrl = addTeacherFilter(url);
            if (filteredUrl !== url) {
                return originalFetch.call(window, filteredUrl, config);
            }
        }
        
        // No changes needed
        return originalFetch.call(window, resource, config);
    };
    
    // Copy over any properties from original fetch
    Object.setPrototypeOf(window.fetch, originalFetch);
    
    console.log('✅ API Redirect + Teacher Filter initialized. Base path:', API_BASE);
})();
