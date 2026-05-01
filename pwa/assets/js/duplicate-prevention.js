/**
 * Duplicate Prevention System - Frontend
 * Generates device fingerprint and prevents duplicate submissions
 */

class DuplicatePreventionManager {
    constructor() {
        this.storageKey = 'teacher_eval_device_id';
        this.deviceId = this.getOrCreateDeviceId();
        this.submissionInProgress = false;
        this.submittedTeachers = this.loadSubmittedTeachers();
    }
    
    /**
     * Get existing device ID or create new one
     */
    getOrCreateDeviceId() {
        let deviceId = localStorage.getItem(this.storageKey);
        
        if (!deviceId) {
            // Generate new device ID
            deviceId = this.generateUniqueId();
            localStorage.setItem(this.storageKey, deviceId);
            console.log('📱 New device ID created:', deviceId);
        }
        
        return deviceId;
    }
    
    /**
     * Generate random unique ID
     */
    generateUniqueId() {
        const timestamp = Date.now().toString(36);
        const random = Math.random().toString(36).substr(2, 9);
        return `dev_${timestamp}_${random}`;
    }
    
    /**
     * Load list of teachers already evaluated from this device
     */
    loadSubmittedTeachers() {
        const stored = localStorage.getItem('teacher_eval_submitted');
        return stored ? JSON.parse(stored) : {};
    }
    
    /**
     * Save list of submitted teachers
     */
    saveSubmittedTeachers() {
        localStorage.setItem('teacher_eval_submitted', JSON.stringify(this.submittedTeachers));
    }
    
    /**
     * Check if teacher was already evaluated from this device
     */
    isTeacherAlreadyEvaluated(teacherId) {
        return !!this.submittedTeachers[teacherId];
    }
    
    /**
     * Mark teacher as evaluated
     */
    markTeacherAsEvaluated(teacherId, teacherName = null) {
        this.submittedTeachers[teacherId] = {
            timestamp: new Date().toISOString(),
            deviceId: this.deviceId,
            name: teacherName || `Teacher ${teacherId}`
        };
        this.saveSubmittedTeachers();
        
        // Update modal handler to reflect new evaluated teacher
        if (window.alreadyEvaluatedModal) {
            window.alreadyEvaluatedModal.updateSubmittedTeachers();
        }
    }
    
    /**
     * Validate before submission
     */
    validateBeforeSubmission(teacherId) {
        if (this.isTeacherAlreadyEvaluated(teacherId)) {
            return {
                valid: false,
                message: '❌ You have already evaluated this teacher from this device. One evaluation per teacher per device.',
                type: 'error'
            };
        }
        
        if (this.submissionInProgress) {
            return {
                valid: false,
                message: '⏳ A submission is in progress. Please wait for it to complete.',
                type: 'warning'
            };
        }
        
        return {
            valid: true,
            message: '✓ Ready to submit',
            type: 'success'
        };
    }
    
    /**
     * Prepare submission data with device info
     */
    prepareSubmissionData(evaluationData) {
        return {
            ...evaluationData,
            device_id: this.deviceId,
            submission_timestamp: new Date().toISOString()
        };
    }
    
    /**
     * Handle successful submission
     */
    onSubmissionSuccess(teacherId) {
        this.submissionInProgress = false;
        this.markTeacherAsEvaluated(teacherId);
        
        // Also notify UI handler to disable the teacher
        if (window.teacherEvaluationUI) {
            window.teacherEvaluationUI.markTeacherAsEvaluated(teacherId);
        }
        
        return {
            message: '✅ Evaluation submitted successfully!',
            type: 'success'
        };
    }
    
    /**
     * Handle submission error
     */
    onSubmissionError(error) {
        this.submissionInProgress = false;
        
        // Parse error message
        const errorMessage = error.message || 'Submission failed';
        
        if (errorMessage.includes('already evaluated')) {
            return {
                message: '❌ You have already evaluated this teacher',
                type: 'error',
                code: 'duplicate'
            };
        }
        
        if (errorMessage.includes('Too many submissions')) {
            return {
                message: '⏳ Too many submissions. Please try again later.',
                type: 'error',
                code: 'rate_limit'
            };
        }
        
        if (errorMessage.includes('in progress')) {
            return {
                message: '⏳ A submission is already in progress',
                type: 'error',
                code: 'already_submitting'
            };
        }
        
        return {
            message: '❌ ' + errorMessage,
            type: 'error',
            code: 'unknown'
        };
    }
    
    /**
     * Get debug info (for testing)
     */
    getDebugInfo() {
        return {
            deviceId: this.deviceId,
            submittedTeachers: this.submittedTeachers,
            localStorage: {
                deviceId: localStorage.getItem(this.storageKey),
                submitted: localStorage.getItem('teacher_eval_submitted')
            }
        };
    }
    
    /**
     * Clear all local data (admin/testing only)
     */
    clearAllData() {
        if (confirm('⚠️ Clear all evaluation data from this device?\n\nThis will allow you to evaluate teachers again.')) {
            localStorage.removeItem(this.storageKey);
            localStorage.removeItem('teacher_eval_submitted');
            this.deviceId = this.getOrCreateDeviceId();
            this.submittedTeachers = {};
            console.log('✓ Cleared');
            return true;
        }
        return false;
    }
}

// Global instance - expose to window for access from other scripts
window.duplicatePrevention = null;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.duplicatePrevention = new DuplicatePreventionManager();
    console.log('🛡️ Duplicate Prevention System initialized');
    console.log('📱 Device ID:', window.duplicatePrevention.deviceId);
    console.log('✅ Available at: window.duplicatePrevention');
});

/**
 * Helper function to validate and submit evaluation
 * Call this before submitting any evaluation form
 */
async function submitEvaluationWithDuplicateCheck(teacherId, evaluationData) {
    if (!duplicatePrevention) {
        console.error('Duplicate prevention not initialized');
        return { success: false, message: 'System error' };
    }
    
    // Validate
    const validation = duplicatePrevention.validateBeforeSubmission(teacherId);
    if (!validation.valid) {
        return { success: false, message: validation.message };
    }
    
    try {
        duplicatePrevention.submissionInProgress = true;
        
        // Add device info
        const dataWithDevice = duplicatePrevention.prepareSubmissionData(evaluationData);
        
        // Submit to API
        const response = await fetch('/api/evaluations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dataWithDevice)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            const successInfo = duplicatePrevention.onSubmissionSuccess(teacherId);
            return {
                success: true,
                message: successInfo.message,
                data: result
            };
        } else {
            const errorInfo = duplicatePrevention.onSubmissionError(result);
            return {
                success: false,
                message: errorInfo.message,
                code: errorInfo.code
            };
        }
        
    } catch (error) {
        duplicatePrevention.submissionInProgress = false;
        const errorInfo = duplicatePrevention.onSubmissionError(error);
        return {
            success: false,
            message: errorInfo.message,
            code: 'network_error'
        };
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { DuplicatePreventionManager, submitEvaluationWithDuplicateCheck };
}
