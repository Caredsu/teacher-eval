# TODO.md - Teacher Duplicate Evaluation Prevention

## Approved Plan Implementation Steps

### 1. Create TODO.md ✅
### 2. Update Backend API (api/check-evaluated-teachers.php)
   - Add support for single teacher_id GET param
   - Query evaluations collection by teacher_id + IP (REMOTE_ADDR)
   - Return JSON: { alreadyEvaluated: true/false }
   - Keep bulk device_id mode for compatibility

### 3. Update Frontend Logic (assets/js/already-evaluated-modal.js)
   - Add per-click check: LocalStorage `evaluated_teacher_{id}` → AJAX if not found
   - Replace custom modal with Bootstrap modal ("You have already evaluated this teacher.")
   - Update submit success: set `evaluated_teacher_{id}` in LocalStorage
   - Keep bulk server load + intercepts as optimization

### 4. Minor Backend Confirmation (api/evaluations.php)
   - Ensure duplicate check + success response triggers frontend LocalStorage

### 5. Add Bootstrap Modal CSS (assets/css/global.css or inline)
   - Standard Bootstrap modal styling

### 6. Test Implementation
   - Update test-already-evaluated-modal.html
   - Test Flutter teacher clicks
   - Verify LocalStorage + AJAX flow

### 7. Complete Task
   - attempt_completion

**Progress: 3/7 (Backend API + Frontend per-click flow + Bootstrap modal + LocalStorage on success)**
