/**
 * API Redirect Fix + Teacher Filtering
 * 1. Intercepts hardcoded IP-based API calls and redirects to relative paths
 * 2. Filters out already-evaluated teachers from the teachers list
 * Must be loaded BEFORE Flutter app initializes
 */

/**
 * API Redirect Fix + Teacher Filtering
 * 1. Intercepts ANY IP-based API calls and redirects to relative paths
 * 2. Filters out already-evaluated teachers from the teachers list
 * Works on ANY IP/hostname (localhost, 127.0.0.1, any LAN IP, production domain)
 * Must be loaded BEFORE Flutter app initializes
 */

(function() {
    // Get the base path from <base> tag (most reliable)
    let API_BASE = '/teacher-eval';
    const baseTag = document.querySelector('base');
    if (baseTag && baseTag.href) {
        const basePath = new URL(baseTag.href).pathname;
        API_BASE = basePath.replace(/\/$/, ''); // Remove trailing slash
    }
    
    console.log('🌐 API Redirect initialized:', {
        currentHost: window.location.hostname,
        protocol: window.location.protocol,
        pathname: window.location.pathname,
        apiBase: API_BASE,
        baseTag: baseTag ? baseTag.href : 'not found'
    });
    
    // Store original fetch
    const originalFetch = window.fetch;
    window.fetch.__originalFetch__ = originalFetch; // Save for other scripts to use
    
    /**
     * Get evaluated teacher IDs from window.alreadyEvaluatedModal (already loaded from server)
     * Falls back to localStorage if modal not available
     */
    function getEvaluatedTeacherIds() {
        try {
            // Prefer data from modal handler (loaded from server)
            if (window.alreadyEvaluatedModal && window.alreadyEvaluatedModal.submittedTeachers) {
                return Object.keys(window.alreadyEvaluatedModal.submittedTeachers);
            }
            
            // Fallback to localStorage
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
        
        // Check if URL is an absolute URL with /teacher-eval path
        // This catches ANY IP/hostname trying to call the API
        const absUrlMatch = url.match(/^https?:\/\/([^\/]+)(\/teacher-eval)?(.*)$/i);
        
        if (absUrlMatch) {
            const requestedHost = absUrlMatch[1];
            const basePath = absUrlMatch[2] || '';
            const requestPath = absUrlMatch[3];
            
            // If it's an absolute URL, convert to relative
            if (requestPath.includes('/api/')) {
                let newUrl = API_BASE + requestPath;
                // Add teacher filter if applicable
                newUrl = addTeacherFilter(newUrl);
                
                console.log('🔄 API Redirect (any IP → relative):', {
                    from: url,
                    to: newUrl,
                    detectedHost: requestedHost
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
