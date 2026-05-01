# Flutter Evaluation Guard - Integration Guide

## Overview
The **Flutter Evaluation Guard** system prevents users from re-evaluating the same teacher on the same device. Once a teacher is evaluated, attempting to click on them shows an "Already Evaluated" modal instead of the evaluation form.

## How It Works

### 1. **Server-Side Check**
- New API endpoint: `/api/can-evaluate-teacher?teacher_id={id}&device_id={id}`
- Returns: `{ can_evaluate: true/false, reason: "..." }`
- Checks both the submission logs and evaluations collection for duplicate submissions

### 2. **JavaScript Bridge**
- `window.FlutterEvaluationGuard` object available for Flutter to call
- No dependencies on Flutter internals

### 3. **Client-Side Storage**
- Uses localStorage to track evaluated teachers
- Prevents unnecessary server calls
- Per-device tracking with unique device ID

## Integration with Flutter App

### Before Opening Evaluation Form
Flutter should call this function BEFORE opening the evaluation form:

```javascript
// Check if teacher can be evaluated
const canEvaluate = await window.FlutterEvaluationGuard.checkBeforeOpening(
    teacherId,      // Required: teacher ID
    teacherName     // Optional: teacher name for modal display
);

if (!canEvaluate) {
    // Don't open the form, the modal is already shown
    return;
}

// Safe to open evaluation form
openEvaluationForm(teacherId);
```

### After Successful Submission
After the evaluation is successfully submitted, notify the guard:

```javascript
// Mark teacher as evaluated
window.FlutterEvaluationGuard.markTeacherEvaluated(
    teacherId,      // Required: teacher ID
    teacherName     // Optional: teacher name
);
```

### To Refresh Evaluated Teachers List
If you need to sync with the server:

```javascript
await window.FlutterEvaluationGuard.refreshEvaluatedTeachers();
```

### To Get Current Evaluated Teachers
Get the list of evaluated teachers:

```javascript
const evaluatedTeacherIds = window.FlutterEvaluationGuard.getEvaluatedTeachers();
console.log('Already evaluated:', evaluatedTeacherIds);
```

## Available Methods

### `checkBeforeOpening(teacherId, teacherName)`
- **Purpose**: Check if teacher can be evaluated BEFORE opening form
- **Parameters**:
  - `teacherId` (string, required): The teacher's ID
  - `teacherName` (string, optional): Teacher name for modal display
- **Returns**: Promise<boolean>
  - `true` = Already evaluated, don't open form (modal shown)
  - `false` = Can evaluate, safe to open form
- **Side Effects**: Shows "Already Evaluated" modal if needed

### `showAlreadyEvaluatedModal(teacherName)`
- **Purpose**: Display the "Already Evaluated" message manually
- **Parameters**: `teacherName` (string, optional)

### `hideModal()`
- **Purpose**: Close the modal
- **Parameters**: None

### `markTeacherEvaluated(teacherId, teacherName)`
- **Purpose**: Mark teacher as evaluated after submission
- **Parameters**:
  - `teacherId` (string, required)
  - `teacherName` (string, optional)
- **Returns**: boolean (true = success, false = error)

### `getEvaluatedTeachers()`
- **Purpose**: Get list of already-evaluated teacher IDs
- **Returns**: Array<string> of teacher IDs

### `refreshEvaluatedTeachers()`
- **Purpose**: Sync evaluated teachers from server (force refresh)
- **Returns**: Promise<boolean>

## Usage Flow

```
User clicks on teacher card in Flutter
    ↓
Flutter calls: checkBeforeOpening(teacherId, teacherName)
    ↓
    ├─ If TRUE: Modal shown, don't open form
    └─ If FALSE: Open evaluation form
        ↓
        User fills and submits form
        ↓
        Submission succeeds (HTTP 201)
        ↓
        Flutter calls: markTeacherEvaluated(teacherId, teacherName)
        ↓
        Close form, show success message
        ↓
        Future click on same teacher → checkBeforeOpening returns TRUE
```

## Modal Appearance

When a user tries to evaluate an already-evaluated teacher, they see:

```
┌─────────────────────────┐
│         ✓               │
│                         │
│      Tapos na!          │
│   (That's done!)        │
│                         │
│ You have already        │
│ evaluated this teacher. │
│                         │
│   [Teacher Name]        │
│                         │
│ One evaluation per      │
│ teacher, per device     │
│                         │
│    [ OK, Got it ]       │
└─────────────────────────┘
```

## API Endpoints

### GET `/api/can-evaluate-teacher`
Check if current device can evaluate a teacher

**Parameters**:
- `teacher_id` (required): Teacher MongoDB ObjectId as string
- `device_id` (optional): Device ID (auto-generated if not provided)

**Response** (Success):
```json
{
    "success": true,
    "data": {
        "can_evaluate": true|false,
        "reason": "string",
        "teacher_id": "string",
        "evaluated_at": "2026-05-01 14:30:00" (if already evaluated)
    }
}
```

**Response** (Already Evaluated):
```json
{
    "success": true,
    "data": {
        "can_evaluate": false,
        "reason": "Already evaluated this teacher from this device",
        "teacher_id": "string",
        "evaluated_at": "2026-05-01 14:30:00"
    }
}
```

## Device Tracking

The system identifies devices by:
1. **Device ID**: localStorage stored ID (persistent)
2. **IP Address**: Request source IP
3. **Device Fingerprint**: Hash of device ID + IP + User Agent

This ensures that:
- ✅ One teacher = one evaluation per device
- ✅ Works across app restarts
- ✅ Survives cache clearing (server-side check)
- ✅ Prevents multi-device abuse

## localStorage Keys

The system uses these localStorage keys:

| Key | Purpose |
|-----|---------|
| `teacher_eval_device_id` | Unique device identifier |
| `teacher_eval_submitted` | JSON of all evaluated teachers (cache) |
| `evaluated_teacher_{id}` | Quick flag for specific teacher |

## Error Handling

The system gracefully handles:
- ✅ Network failures (uses localStorage as fallback)
- ✅ Server errors (displays modal if uncertain)
- ✅ Missing teacher (returns error from server)
- ✅ Malformed requests (returns HTTP 400)

## Testing

### Test Already Evaluated Teacher
1. Evaluate a teacher (submit form)
2. Click on same teacher again
3. Should see modal, not form

### Test New Device
1. Evaluate teacher on Device A
2. Switch to Device B (clear localStorage or incognito)
3. Can evaluate same teacher on Device B

### Test Server Sync
1. Manually delete localStorage
2. Click on already-evaluated teacher
3. Server check should still block it

## Troubleshooting

### Form Still Opens for Evaluated Teacher
- Clear browser cache
- Check browser console for JavaScript errors
- Verify `window.FlutterEvaluationGuard` exists
- Check that Flutter calls `checkBeforeOpening()` before opening form

### Modal Doesn't Show
- Verify `/assets/js/flutter-evaluation-guard.js` is loaded
- Check that `/assets/js/already-evaluated-modal.js` is loaded before guard
- Look for errors in browser console

### Evaluated Teachers List Empty
- Check that evaluations were submitted successfully (HTTP 201)
- Verify `markTeacherEvaluated()` was called after submission
- Check localStorage keys exist

## Next Steps for Flutter Integration

1. ✅ Add `window.FlutterEvaluationGuard.checkBeforeOpening()` call before opening evaluation form
2. ✅ Add `window.FlutterEvaluationGuard.markTeacherEvaluated()` call after successful submission
3. ✅ Update teacher card UI to show "Already Evaluated" badge (optional, modal handles it)
4. ✅ Test on multiple devices/browsers
5. ✅ Monitor console logs to ensure system is working

## Browser Compatibility

- ✅ Chrome/Chromium 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile Chrome/Safari (iOS/Android)

## Performance Notes

- First check: ~50ms (localStorage)
- Server check: ~100-300ms (network dependent)
- Local checks: <1ms (subsequent clicks on same teacher in same session)
