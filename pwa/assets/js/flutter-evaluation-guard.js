/**
 * Flutter Evaluation Guard
 * Provides a bridge between Flutter app and JavaScript to prevent re-evaluation
 * Exposes functions that Flutter can call to check if teacher is already evaluated
 */

window.FlutterEvaluationGuard = {
    /**
     * Check if teacher is already evaluated and show modal if needed
     * Flutter should call this BEFORE opening the evaluation form
     * @param {string} teacherId - The teacher ID
     * @param {string} teacherName - The teacher name for display
     * @returns {boolean} true if teacher is already evaluated, false if can proceed
     */
    async checkBeforeOpening(teacherId, teacherName = 'This Teacher') {
        console.log('🔍 Flutter Guard: Checking teacher', teacherId);
        
        try {
            // Wait for modal handler to be ready
            if (!window.alreadyEvaluatedModal) {
                console.warn('⚠️ Modal handler not ready yet, waiting...');
                await new Promise(resolve => setTimeout(resolve, 500));
            }
            
            // Get device ID
            const deviceId = window.alreadyEvaluatedModal?.getOrCreateDeviceId?.() || localStorage.getItem('teacher_eval_device_id');
            
            // Check server for accurate status
            const apiUrl = `/teacher-eval/api/can-evaluate-teacher?teacher_id=${encodeURIComponent(teacherId)}&device_id=${encodeURIComponent(deviceId || '')}`;
            
            console.log('📡 Checking server:', apiUrl);
            
            const response = await fetch(apiUrl);
            const data = await response.json();
            
            if (data.success && !data.data.can_evaluate) {
                console.log('✓ Server says: already evaluated', teacherId);
                this.showAlreadyEvaluatedModal(teacherName);
                return true; // Block form opening
            }
            
            if (data.success && data.data.can_evaluate) {
                console.log('✓ Server says: can evaluate', teacherId);
                return false; // Allow form to open
            }
            
            // Fallback: check localStorage
            const key = `evaluated_teacher_${teacherId}`;
            if (localStorage.getItem(key)) {
                console.log('✓ Found in localStorage - already evaluated:', teacherId);
                this.showAlreadyEvaluatedModal(teacherName);
                return true; // Block form opening
            }
            
            console.log('✓ Teacher not evaluated - allow form to open:', teacherId);
            return false; // Allow form to open
            
        } catch (error) {
            console.error('❌ Error checking evaluation status:', error);
            // On error, check localStorage as fallback
            const key = `evaluated_teacher_${teacherId}`;
            if (localStorage.getItem(key)) {
                console.warn('⚠️ Server check failed, using localStorage');
                this.showAlreadyEvaluatedModal(teacherName);
                return true;
            }
            // On error, allow to proceed (fail open, not fail closed)
            return false;
        }
    },

    /**
     * Show the "already evaluated" modal
     * Flutter calls this to display the message to user
     */
    showAlreadyEvaluatedModal(teacherName = 'This Teacher') {
        if (window.alreadyEvaluatedModal) {
            window.alreadyEvaluatedModal.showModal(teacherName);
            console.log('📢 Showing already evaluated modal for:', teacherName);
        } else {
            console.error('❌ Modal handler not available');
        }
    },

    /**
     * Hide the modal (if needed)
     */
    hideModal() {
        if (window.alreadyEvaluatedModal) {
            window.alreadyEvaluatedModal.hideModal();
        }
    },

    /**
     * Mark teacher as evaluated (call this after successful submission)
     * Flutter calls this to update the evaluated teachers list
     */
    markTeacherEvaluated(teacherId, teacherName = 'This Teacher') {
        console.log('✅ Flutter Guard: Marking as evaluated', teacherId);
        
        try {
            // Mark in localStorage
            const key = `evaluated_teacher_${teacherId}`;
            localStorage.setItem(key, 'true');
            
            // Update the modal handler
            if (window.alreadyEvaluatedModal) {
                window.alreadyEvaluatedModal.submittedTeachers[teacherId] = {
                    timestamp: new Date().toISOString(),
                    name: teacherName,
                    marked_at: new Date().getTime()
                };
                window.alreadyEvaluatedModal.syncLocalStorageWithServer();
                window.alreadyEvaluatedModal.informFlutterDisable(teacherId);
            }
            
            // Disable in UI if available
            if (window.teacherEvaluationUI) {
                window.teacherEvaluationUI.markTeacherAsEvaluated(teacherId);
            }
            
            return true;
        } catch (error) {
            console.error('❌ Error marking teacher as evaluated:', error);
            return false;
        }
    },

    /**
     * Get list of already evaluated teacher IDs
     * Flutter can call this to update its UI state
     */
    getEvaluatedTeachers() {
        if (window.alreadyEvaluatedModal) {
            const teacherIds = window.alreadyEvaluatedModal.getEvaluatedTeacherIds();
            console.log('📊 Returning evaluated teachers:', teacherIds);
            return teacherIds;
        }
        return [];
    },

    /**
     * Force refresh the evaluated teachers list from server
     * Useful if the list gets out of sync
     */
    async refreshEvaluatedTeachers() {
        console.log('🔄 Refreshing evaluated teachers from server...');
        try {
            if (window.alreadyEvaluatedModal) {
                await window.alreadyEvaluatedModal.loadEvaluatedTeachersFromServer();
                console.log('✅ Evaluated teachers refreshed');
                return true;
            }
        } catch (error) {
            console.error('❌ Error refreshing evaluated teachers:', error);
            return false;
        }
    }
};

console.log('✅ Flutter Evaluation Guard initialized');
console.log('Flutter can call: window.FlutterEvaluationGuard.checkBeforeOpening(teacherId, teacherName)');
