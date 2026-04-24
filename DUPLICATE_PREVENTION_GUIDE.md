# 🛡️ Duplicate Submission Prevention System

**Added:** April 24, 2026  
**Status:** ✅ Ready for production  

---

## Overview

This system prevents spam and multiple evaluations from the same device/location for anonymous teacher evaluations. It uses multi-factor device fingerprinting and intelligent rate limiting.

---

## ⚙️ How It Works

### 1. Device Fingerprinting
Each student device gets a unique identifier based on:
- **Device ID**: Stored in browser localStorage (persists across visits)
- **IP Address**: Network location
- **User Agent**: Browser/device details

The system generates a SHA-256 hash combining all three for robust duplicate detection.

### 2. Duplicate Checks (in order)

```
┌─────────────────────────────────────────────────────────┐
│         DUPLICATE SUBMISSION PREVENTION FLOW            │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ ① PERMANENT DUPLICATE CHECK                            │
│    └─ Already evaluated THIS teacher from THIS device?  │
│    └─ BLOCK: "One evaluation per teacher per device"    │
│                                                         │
│ ② RATE LIMIT (per device)                              │
│    └─ More than 3 evaluations in last hour?             │
│    └─ BLOCK: "Too many submissions. Try later"          │
│                                                         │
│ ③ RATE LIMIT (per IP)                                  │
│    └─ More than 10 evaluations from same IP in 1 hour?  │
│    └─ BLOCK: Prevents coordinated spam attacks          │
│                                                         │
│ ④ IN-PROGRESS CHECK                                    │
│    └─ Already submitting same teacher from this device? │
│    └─ BLOCK: "Wait for previous submission to complete" │
│                                                         │
│ ✓ ALL CHECKS PASS → Allow submission                    │
└─────────────────────────────────────────────────────────┘
```

### 3. Submission Tracking
Every submission attempt is logged with:
- Teacher ID
- Device fingerprint
- IP address
- User agent
- Timestamp
- Status (pending → completed)

---

## 🚀 Installation & Setup

### 1. Initialize the System

```bash
php scripts/init-duplicate-prevention.php
```

This creates:
- `submission_logs` collection
- Performance indexes
- TTL cleanup for expired records

### 2. Include in HTML

Add to your student evaluation page `<head>`:

```html
<!-- Duplicate prevention system -->
<script src="/assets/js/duplicate-prevention.js"></script>
```

### 3. Update Evaluation Form Submission

Before submitting, validate with the system:

```javascript
// When form is submitted
document.getElementById('evaluationForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const teacherId = document.getElementById('teacher_id').value;
    const evaluationData = {
        teacher_id: teacherId,
        answers: getFormAnswers(),
        feedback: document.getElementById('feedback').value
    };
    
    // Check for duplicates and submit
    const result = await submitEvaluationWithDuplicateCheck(teacherId, evaluationData);
    
    if (result.success) {
        showSuccess(result.message);
        // Redirect or clear form
    } else {
        showError(result.message);
    }
});
```

---

## 📱 Frontend API

### DuplicatePreventionManager

```javascript
// Get current device ID
duplicatePrevention.deviceId
// → "dev_1pq8vbx_a1b2c3d4e"

// Check if teacher was already evaluated
if (duplicatePrevention.isTeacherAlreadyEvaluated(teacherId)) {
    console.log('Already evaluated');
}

// Validate before submission
const validation = duplicatePrevention.validateBeforeSubmission(teacherId);
if (!validation.valid) {
    showError(validation.message);
}

// Prepare data with device info
const data = duplicatePrevention.prepareSubmissionData({
    teacher_id: '123',
    answers: { q1: 5, q2: 4 },
    feedback: 'Great teacher!'
});

// Get debug information
console.table(duplicatePrevention.getDebugInfo());

// Clear all data (admin/testing only)
duplicatePrevention.clearAllData();
```

### Helper Function

```javascript
// Submit with automatic duplicate checks
const result = await submitEvaluationWithDuplicateCheck(teacherId, {
    teacher_id: teacherId,
    answers: answers,
    feedback: feedback
});

if (result.success) {
    // Success - evaluation submitted
    console.log('Submitted:', result.data);
} else {
    // Failed - show reason
    console.log('Error:', result.message);
    console.log('Code:', result.code);
    // Codes: 'duplicate', 'rate_limit', 'network_error', etc.
}
```

---

## 🔧 Backend API

### Functions in `includes/duplicate-prevention.php`

```php
// Check for duplicate
$check = checkDuplicateSubmission($teacherId, $deviceId, $ipAddress, $userAgent);
if ($check['is_duplicate']) {
    sendError($check['message'], 400);
}

// Log submission attempt
$logId = logSubmissionAttempt($teacherId, 'pending', $deviceId, $ipAddress, $userAgent);

// Update status after successful submission
updateSubmissionLogStatus($logId, 'completed');

// Create indexes (called by init script)
createSubmissionLogIndexes();

// Generate device fingerprint
$fingerprint = generateDeviceFingerprint($deviceId, $ipAddress, $userAgent);
```

---

## 📊 Submission Logs Structure

Collection: `submission_logs`

```javascript
{
  _id: ObjectId,
  teacher_id: ObjectId,                    // Which teacher
  device_fingerprint: "abc123def456...",   // SHA-256 hash
  device_id: "dev_1pq8vbx_a1b2c3d4e",     // Browser localStorage ID
  ip_address: "192.168.1.1",               // Network location
  user_agent: "Mozilla/5.0 Chrome/...",    // Browser details
  status: "pending|completed",              // Current state
  submitted_at: ISODate(...),              // When submitted
  updated_at: ISODate(...),                // Last updated
  
  // Indexes:
  // { teacher_id: 1, device_fingerprint: 1, status: 1 }
  // { ip_address: 1, status: 1, submitted_at: 1 }
  // { submitted_at: 1 } // TTL: expires after 24h if pending
}
```

---

## 📈 Rate Limits

| Limit | Value | Purpose |
|-------|-------|---------|
| Per device per hour | 3 evaluations | Prevents individual spam |
| Per IP per hour | 10 evaluations | Prevents coordinated attacks |
| Per teacher per device | 1 evaluation | Prevents re-voting |
| Pending timeout | 5 minutes | Recovery from failed submissions |
| Auto-cleanup | 24 hours | Removes failed submission logs |

---

## 🧪 Testing

### Check Device Info (JavaScript Console)

```javascript
// View current state
console.table(duplicatePrevention.getDebugInfo());

// Output:
// {
//   deviceId: "dev_1pq8vbx_a1b2c3d4e",
//   submittedTeachers: {
//     "teacher_123": { timestamp: "2026-04-24T10:15:00Z", deviceId: "dev_..." },
//     "teacher_456": { timestamp: "2026-04-24T10:20:00Z", deviceId: "dev_..." }
//   },
//   localStorage: { ... }
// }
```

### Test Duplicate Prevention

```javascript
// 1. Evaluate a teacher
await submitEvaluationWithDuplicateCheck('teacher_123', { ... });
// → Success ✅

// 2. Try again immediately
await submitEvaluationWithDuplicateCheck('teacher_123', { ... });
// → Error: "You have already evaluated this teacher"

// 3. Evaluate different teacher
await submitEvaluationWithDuplicateCheck('teacher_456', { ... });
// → Success ✅

// 4. Check localStorage
localStorage.getItem('teacher_eval_submitted');
// → {"teacher_123": {...}, "teacher_456": {...}}
```

### Admin Testing

```javascript
// Clear all evaluations (testing only)
duplicatePrevention.clearAllData();
// → Asks for confirmation, clears localStorage
// → Can now evaluate all teachers again
```

### Database Inspection

```bash
# MongoDB queries to inspect
db.submission_logs.find({ status: 'completed' }).limit(10);
db.submission_logs.find({ ip_address: '192.168.1.1' });
db.submission_logs.aggregate([
  { $match: { status: 'completed' } },
  { $group: { _id: '$device_fingerprint', count: { $sum: 1 } } },
  { $sort: { count: -1 } }
]);
```

---

## 🎯 Real-World Scenarios

### Scenario 1: Legitimate Student

1. Student opens evaluation page
2. System generates device ID and stores in localStorage
3. Student selects teacher and completes evaluation
4. Submission succeeds ✅
5. Student tries to evaluate same teacher again
6. System blocks with: "You have already evaluated this teacher" ❌
7. Student can evaluate other teachers ✅

### Scenario 2: Student Returns Next Day

1. Student opens evaluation page (new device or cleared cache)
2. Different device ID generated
3. System allows new evaluation (different device) ✅
4. Previous device ID still remembered in old device's localStorage

### Scenario 3: Spam Attack

1. Attacker tries 10 rapid submissions from same IP
2. After 3: Rate limit per device triggers ❌
3. If using 5 different devices from same IP: IP limit (10 total) triggers ❌
4. Subsequent requests blocked with: "Too many submissions from your location" ❌

### Scenario 4: Network Failure During Submission

1. Student submits evaluation
2. Network drops mid-submission
3. Submission logged as "pending" (not completed)
4. After 5 minutes: can retry (in-progress check passes)
5. After 24 hours: pending log auto-deleted by TTL index

---

## 📋 Monitoring & Analytics

### Get Evaluation Count by Device

```php
$pipeline = [
    ['$match' => ['status' => 'completed']],
    ['$group' => [
        '_id' => '$device_fingerprint',
        'count' => ['$sum' => 1],
        'teachers' => ['$push' => '$teacher_id']
    ]],
    ['$sort' => ['count' => -1]],
    ['$limit' => 10]
];
$results = $evaluations_collection->aggregate($pipeline)->toArray();
```

### Detect Spam Patterns

```php
// IPs with more than 5 evaluations in 1 hour
$pipeline = [
    ['$match' => [
        'status' => 'completed',
        'submitted_at' => ['$gte' => new MongoDB\BSON\UTCDateTime((time() - 3600) * 1000)]
    ]],
    ['$group' => [
        '_id' => '$ip_address',
        'count' => ['$sum' => 1],
        'devices' => ['$addToSet' => '$device_fingerprint']
    ]],
    ['$match' => ['count' => ['$gte' => 5]]],
    ['$sort' => ['count' => -1]]
];
```

---

## ⚠️ Edge Cases

| Case | Behavior |
|------|----------|
| Shared device (family computer) | Each teacher can only be evaluated once per family |
| School lab computers | All student evaluations from same IP, but different devices ok |
| VPN/Proxy | IP changes, so rate limit resets |
| Incognito/Private browsing | New device ID each time (intentional isolation) |
| Browser cache cleared | New device ID generated, previous evaluations forgotten locally |
| Multiple browsers | Different device IDs, rate limits reset |

---

## 🔐 Security Notes

1. **Client-side checks are NOT secure** - they just improve UX
2. **Server-side validation is authoritative** - what matters for security
3. **Device ID can be spoofed** - but rate limiting by IP prevents mass attacks
4. **Anonymous evaluations stay anonymous** - device data not linked to names

---

## 🚨 Troubleshooting

### "Device ID not generating"
- Check if localStorage is enabled in browser settings
- Try different browser or incognito mode
- Check browser console for errors

### "Can't submit evaluation that's already done"
- This is intentional - designed to prevent duplicates
- Use admin console: `duplicatePrevention.clearAllData()`
- Or use different browser/device

### "Getting rate limit error"
- Wait 1 hour before submitting again
- Or use different device/network
- Or clear browser cache + localStorage

### Database already has data
- Init script is safe to run again (idempotent)
- Existing data won't be affected
- Indexes will be checked, not recreated if present

---

## 📞 Support

For issues or questions, check:
1. Browser console for client-side errors
2. Server logs for API errors
3. MongoDB submission_logs collection for tracking data
4. Implementation examples in Flutter app integration

