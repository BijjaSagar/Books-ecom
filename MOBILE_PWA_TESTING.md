# Mobile PWA Testing Guide

## 📱 PWA Installation & Testing

### System Requirements

- **iOS:** iPhone/iPad with iOS 11.3+
- **Android:** Android 5.1+ with Chrome/Samsung Internet
- **Desktop:** Chrome, Edge, Safari 16+

---

## 🚀 Installation Methods

### **iOS (iPhone/iPad)**

#### Method 1: Safari "Add to Home Screen"
1. Open Safari browser
2. Navigate to: `https://your-domain.com/admin/mobile-dashboard.php`
3. Tap **Share** button (bottom menu)
4. Tap **Add to Home Screen**
5. Give it a name (e.g., "Bookory Admin")
6. Tap **Add**

#### Method 2: Manual Home Screen
1. Open mobile dashboard
2. Look for "Install" button/prompt
3. Tap to add to home screen

**Result:** App appears on home screen with icon, works offline

---

### **Android (Chrome)**

#### Method 1: Chrome "Install App"
1. Open Chrome browser
2. Navigate to: `https://your-domain.com/admin/mobile-dashboard.php`
3. Tap **Menu** (3 dots)
4. Tap **Install app** or **Add to Home Screen**
5. Confirm installation

#### Method 2: Using URL Bar
1. Open app in Chrome
2. Tap address bar
3. Look for **Install** icon/option
4. Tap to install

**Result:** App installs like native app

---

### **Samsung Internet**
1. Open Samsung Internet browser
2. Navigate to dashboard
3. Tap **Menu** (3 lines)
4. Tap **Add page to Home screen**
5. Confirm

---

## ✅ Testing Checklist

### Basic Functionality Tests

- [ ] **Load Dashboard**
  - App loads quickly
  - All metrics display correctly
  - No console errors

- [ ] **Navigation**
  - Bottom navigation bar works
  - Page transitions smooth
  - Links navigate correctly
  - Back button works

- [ ] **Responsive Design**
  - Layout adapts to screen size
  - Touch targets are large (min 48px)
  - No horizontal scrolling
  - Safe area respected (notch, etc.)

- [ ] **Performance**
  - First load < 3 seconds
  - Subsequent loads < 1 second
  - Smooth scrolling
  - No lag when tapping buttons

- [ ] **Offline Functionality**
  - Disable internet
  - App still loads
  - Cached data displays
  - Offline indicator shows
  - No error messages for expected behavior

### Feature Tests

#### Dashboard Metrics
- [ ] Revenue display correct
- [ ] Order count accurate
- [ ] Customer count shows
- [ ] Average order value calculates

#### Top Products
- [ ] List displays
- [ ] Product names visible
- [ ] Sales data shows
- [ ] Badges display correctly

#### Order Status
- [ ] Status breakdown shows
- [ ] Badge colors correct
- [ ] Count numbers accurate

#### Quick Actions
- [ ] Add Product button works
- [ ] View Orders button works
- [ ] Check Inventory button works
- [ ] Bulk Upload button works

### Offline Mode Tests

1. **Enable Offline Mode**
   ```bash
   # In browser DevTools
   Chrome DevTools → Network → Offline
   ```

2. **Test Functionality**
   - [ ] Page loads from cache
   - [ ] Offline indicator appears
   - [ ] Data displays from cache
   - [ ] Buttons don't crash app
   - [ ] Sync button available

3. **Return Online**
   - [ ] Offline indicator disappears
   - [ ] Auto-sync triggers
   - [ ] Fresh data loads
   - [ ] App fully functional

---

## 🔧 Developer Testing (Desktop)

### Chrome DevTools PWA Testing

1. **Open DevTools**
   ```
   Chrome Menu → More tools → Developer tools
   Or: F12 or Ctrl+Shift+I
   ```

2. **Simulate Mobile Device**
   - Click **Device Toggle** (icon top-left)
   - Select iOS or Android device
   - Reload page

3. **Test Service Worker**
   - Go to **Application** tab
   - Click **Service Workers**
   - Check:
     - [x] Service Worker registered
     - [x] Status: activated
     - [x] Scope: correct

4. **Test Application Cache**
   - Go to **Application** tab
   - Click **Cache Storage**
   - Verify caches exist:
     - `bookory-admin-v1`
     - `bookory-runtime-v1`

5. **Test Offline**
   - Go to **Network** tab
   - Check **Offline** checkbox
   - Reload page
   - Should still load from cache

6. **Test Manifest**
   - Go to **Application** tab
   - Click **Manifest**
   - Verify:
     - [x] name: "Bookory - Book E-commerce Dashboard"
     - [x] short_name: "Bookory Admin"
     - [x] start_url: correct
     - [x] display: "standalone"
     - [x] icons present

---

## 📊 Performance Testing

### Lighthouse Audit

1. **Run Lighthouse**
   - DevTools → Lighthouse tab
   - Select:
     - [x] Performance
     - [x] Accessibility
     - [x] Best Practices
     - [x] SEO
     - [x] PWA
   - Click "Analyze page load"

2. **Expected Scores**
   - Performance: 90+
   - Accessibility: 90+
   - Best Practices: 90+
   - SEO: 90+
   - PWA: 100

3. **Performance Metrics**
   - First Contentful Paint (FCP): < 3s
   - Largest Contentful Paint (LCP): < 4s
   - Cumulative Layout Shift (CLS): < 0.1

### Memory & Battery Tests

```javascript
// Check memory usage
console.memory

// Monitor battery (Android)
navigator.getBattery().then(function(battery) {
    console.log('Battery level: ' + battery.level * 100 + '%');
});
```

---

## 🌐 Network Testing

### Test on Slow Network

1. **Chrome DevTools**
   - Network tab
   - Select "Slow 4G" or "Fast 3G"
   - Reload page
   - Verify loads acceptably

2. **Expected Times (Slow 4G)**
   - Initial load: 5-10 seconds
   - Subsequent loads: 1-2 seconds
   - API calls: 2-5 seconds

### Test on Various Networks

- [ ] WiFi (5GHz)
- [ ] WiFi (2.4GHz)
- [ ] 4G LTE
- [ ] 3G
- [ ] Offline (cached)

---

## 📸 Screenshot Testing

### Required Screenshots (for app stores)

Create these images in `/images/`:

**App Icon:**
- `logo-192.png` (192x192)
- `logo-512.png` (512x512)
- `logo-maskable.png` (192x192, maskable format)

**Screenshots (for app stores):**
- `screenshot1.png` (540x720)
- `screenshot2.png` (540x720)
- `screenshot3.png` (540x720)

### How to Create Screenshots

1. **Desktop Screenshot**
   ```
   Mobile device simulation in Chrome
   F12 → Device mode → Capture screenshot
   ```

2. **Actual Device Screenshot**
   - iOS: Hold Volume Down + Power
   - Android: Hold Power + Volume Down

3. **Edit Screenshots**
   - Add callouts/arrows
   - Highlight key features
   - Add text descriptions

---

## 🔐 Security Testing

### Test Data Protection

- [ ] Session persists when closed
- [ ] Logout clears session
- [ ] Private data not exposed
- [ ] No sensitive info in logs
- [ ] HTTPS enforced
- [ ] Service Worker only caches appropriate data

### Test Authentication

- [ ] Login works
- [ ] Invalid credentials rejected
- [ ] Session timeout works
- [ ] Logout clears cache
- [ ] Can't access protected pages without auth

### Test API Security

- [ ] API calls include auth
- [ ] Invalid requests rejected
- [ ] Rate limiting works
- [ ] No CORS errors

---

## 🐛 Bug Testing

### Common Issues to Check

- [ ] Keyboard doesn't cover inputs
- [ ] Touch targets large enough
- [ ] Text is readable
- [ ] Colors have good contrast
- [ ] No broken images
- [ ] Forms submit correctly
- [ ] Errors are clear
- [ ] No console errors
- [ ] No memory leaks
- [ ] Smooth animations

### Test Form Inputs

```html
<!-- Test these form elements -->
<input type="text">      <!-- Text input -->
<input type="email">     <!-- Email input -->
<input type="date">      <!-- Date picker -->
<select>                 <!-- Dropdown -->
<textarea>              <!-- Text area -->
<button>                <!-- Button tap -->
```

---

## 📝 Test Report Template

Create `TEST_REPORT.md`:

```markdown
# PWA Testing Report

**Date:** YYYY-MM-DD
**Tester:** Name
**Device:** iPhone 12 / Samsung S21 / etc.
**Browser:** Safari / Chrome / Samsung Internet
**OS Version:** iOS 16 / Android 12 / etc.

## Installation
- [x] App installed successfully
- [x] Icon appears on home screen
- [x] Launches in standalone mode

## Functionality
- [x] All features working
- [x] No crashes
- [x] Offline mode works
- [x] Data displays correctly

## Performance
- [x] Loads quickly
- [x] Smooth interactions
- [x] No freezing
- [x] Battery usage normal

## Issues Found
1. (None / List any issues)

## Recommendations
- (Improvements to make)

**Overall Status:** ✓ PASS / ✗ FAIL
```

---

## 🚀 Pre-Deployment Checklist

Before going live, verify:

- [ ] Service Worker registered
- [ ] Manifest.json valid
- [ ] Icons present and correct size
- [ ] HTTPS enabled
- [ ] App loads offline
- [ ] No console errors
- [ ] Performance scores 90+
- [ ] All tests pass
- [ ] Responsive on all sizes
- [ ] Fast on slow networks

---

## 📱 Real Device Testing

### Testing on Real Devices

1. **iOS Device**
   ```bash
   # Connect to local server
   # Use your computer's IP address
   http://192.168.1.xxx/admin/mobile-dashboard.php
   ```

2. **Android Device**
   ```bash
   # Same as iOS
   # Enable USB debugging for Chrome DevTools
   ```

3. **Test on Multiple Devices**
   - iPhone XS, 12, 13, 14
   - Samsung S20, S21, S22
   - Older devices (performance test)

---

## ✨ Advanced Testing

### Test Push Notifications

```javascript
// Request notification permission
Notification.requestPermission().then(permission => {
    if (permission === 'granted') {
        console.log('Notifications enabled');
    }
});
```

### Test Background Sync

```javascript
// Register sync event
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.ready.then(registration => {
        registration.sync.register('sync-data');
    });
}
```

### Test Gestures

- [ ] Swipe navigation
- [ ] Pull to refresh
- [ ] Long press context menu
- [ ] Pinch zoom (if enabled)
- [ ] Double tap

---

## 📊 Testing Results

### Document Results

Create test results in `/test-results.json`:

```json
{
  "date": "2024-11-09",
  "device": "iPhone 14",
  "browser": "Safari",
  "tests": {
    "installation": "PASS",
    "offline": "PASS",
    "performance": "PASS",
    "security": "PASS",
    "accessibility": "PASS"
  },
  "lighthouse_score": 95,
  "issues_found": [],
  "recommendations": []
}
```

---

## 🔄 Continuous Testing

After deployment:

1. **Monitor Performance**
   - Use Google Analytics
   - Check Lighthouse scores monthly
   - Monitor error rates

2. **Gather User Feedback**
   - In-app feedback form
   - App store ratings
   - User surveys

3. **Regular Testing**
   - Monthly on new device OS versions
   - After any code updates
   - Before major releases

---

**Last Updated:** November 2024
**Status:** Production Ready
