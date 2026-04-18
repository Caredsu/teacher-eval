# Teacher Evaluation PWA - Student App

Flutter Web PWA for students to complete teacher evaluations.

## Features

- 📱 Progressive Web App (works offline)
- 🔌 Auto-sync when connection available
- 📊 Real-time evaluation status
- 🎯 Simple and intuitive interface
- 🔒 Secure data submission

## Installation

### Build from Source

```bash
flutter pub get
flutter build web --release
```

### Deploy

See main `teacher-eval` repository deployment guide.

## Usage

1. Open app in browser: `https://your-domain/flutter-app`
2. Wait for evaluation period to open
3. Select teacher to evaluate
4. Complete the survey
5. Submit evaluation

## Configuration

API endpoints are configured in `eval-status-check.js` and API service.

## Browser Support

- Chrome/Chromium 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Offline Support

The app uses Service Workers to provide offline functionality:
- Can browse questions while offline
- Evaluations are queued and submitted when online
- Automatic sync when connection restored

## License

All rights reserved. School use only.
