# Security Analysis Report — `client.vrxtool.wingo1`

**File:** `app-release.apk`  
**Package:** `client.vrxtool.wingo1`  
**Build:** AGP 9.0.1, minSdk 23, targetSdk 34, versionCode 1  
**Date:** 2026-09-25  

---

## 1. Executive Summary

This application is **not a prank tool**. It is a live, subscription-based **prediction signal overlay** for the Wingo color-prediction gambling platform. It connects to a real backend server, registers each user's device, fetches paid "products" (signal plans), and renders a draggable floating overlay on top of other apps showing real-time predictions and an account balance in Indian Rupees (INR ₹). A hard usage limit ("limit_left") is enforced server-side, consistent with a paid subscription model. The app is distributed outside official app stores and has strong anti-analysis measures to prevent security researchers from intercepting its traffic or inspecting its behavior.

---

## 2. Backend Infrastructure

| Item | Value |
|---|---|
| API Base URL | `https://ubfuturetech.com/vrx_ai_tool_robrother1/api/` |
| API Key | `lzr_turbo_api_key_v2.0` |
| Secret Key | `LZR_TURBO_SECRET_2026` |
| Storage | Android `SharedPreferences` (`pvt_data`) |
| Encoding | All three values are Base64-encoded inline in `MainActivity.onCreate()` |

These credentials are trivially decoded (standard Base64, no encryption). Anyone with the APK can extract the full API key and secret.

### API Endpoints Called

| Endpoint | Method | Purpose |
|---|---|---|
| `register_device` | POST | Registers device on first launch |
| `get_main_product` | POST | Fetches the primary signal/product |
| `product_lists` | POST | Fetches list of available subscription plans |
| `get_config` | POST | Fetches per-user config (username, limits, overlay settings) |

All requests are authenticated with:
- `device_id` (Android `ANDROID_ID` or random UUID)
- `api_key`
- `timestamp` (Unix epoch)
- `signature` = MD5(`device_id` + `secret_key` + `timestamp`)

**Weakness:** MD5 is cryptographically broken. The signature scheme is trivially forgeable once the secret key is known (and it is — it's in the APK).

---

## 3. Anti-Analysis Measures

The app implements four layers of anti-analysis in **every Activity** (`MainActivity`, `HomeActivity`, `CreateActivity`):

### 3.1 Emulator Detection
```java
if (Build.FINGERPRINT.contains("generic") ||
    Build.MODEL.contains("Emulator") ||
    Build.HARDWARE.contains("goldfish")) {
    // kill process
}
```

### 3.2 Root Detection
Checks for `su` binary existence in six common paths:
- `/system/app/Superuser.apk`, `/sbin/su`, `/system/bin/su`,
  `/system/xbin/su`, `/data/local/xbin/su`, `/data/local/bin/su`

### 3.3 Debugger Detection
```java
if (Debug.isDebuggerConnected()) { /* kill */ }
```
Called in every `onResume()`.

### 3.4 VPN / Proxy Detection
Checks `ConnectivityManager` for a VPN-type network (`TYPE_VPN = 17`) and
also checks `Proxy.getHost()` / `Proxy.getPort()` for a system proxy.
Any detected VPN or proxy kills the app immediately.

**Purpose:** These measures exist specifically to prevent security researchers from intercepting API traffic (e.g. via Burp Suite or mitmproxy), running the app in an analysis environment, or attaching a debugger. This is not behaviour you add to a prank app.

---

## 4. Screen Overlay (SYSTEM_ALERT_WINDOW)

The most significant feature. `HomeActivity.s()` and `HomeActivity.t()` create **two draggable floating overlays** that draw on top of all other applications, including the Wingo platform itself.

### Overlay 1 (`flot1` layout)
- Displays: username, product name/image (loaded via Glide from backend URL), limit counter, balance amount
- Runs a **2-second repeating timer** — likely polling the backend for signal updates
- Runs a **1-second countdown timer** (`textview p1`, `textview p2`)
- Has an expandable panel (`frameLayout R.id.mod`) that appears on tap
- Contains a Lottie animation (likely a "processing" or "win" animation)
- Shows `textview8` = current limit value from `B.getText()` ("limit_left")

### Overlay 2 (`flot2` layout)
- Displays: device manufacturer/model, Android version, INR amount (`INR ₹{register_amount}`)
- Has a Switch (`s2`, default ON) — likely toggles signal mode
- Runs two timers at **100ms interval** — high-frequency UI updates
- Shows seven data fields (`m1`–`m7`)
- Has a clickable action row (`s1`)

**Assessment:** The overlays are designed to sit on top of the Wingo app while a user places bets, showing predicted outcomes and a countdown. The 100ms timer suggests live countdown to a game round. The INR denomination confirms the target market is Indian users of Wingo.

---

## 5. Subscription / Limit System

- `limit_left` is stored in `SharedPreferences` and displayed live in the overlay
- `register_amount` defaults to `"900"` (₹900) — this is likely the displayed account balance
- The server controls both values via the `get_config` endpoint
- `BlockActivity` shows a "blocked" screen when access is revoked, with a **Telegram button** to contact the operator
- `MaintenanceActivity` shows a maintenance screen, also with a **Telegram button**

The Telegram integration is a standard pattern for illegal/grey-market signal app operators who sell access and handle disputes via Telegram channels.

---

## 6. Developer Fingerprints

`CreateActivity` contains a syntax highlighter with a custom regex pattern:

```
\b(Uzuakoli|Amoji|Bright|Ndudirim|Ezinwanne|Lightworker|Isuochi|Abia|Ngodo)\b
```

These are all locations or names associated with **Abia State, Nigeria** (Uzuakoli and Isuochi are towns in Abia State). This strongly suggests the developer is from that region. The app name fragment `robrother1` in the API path and the backend domain `ubfuturetech.com` may be additional developer identifiers.

---

## 7. Security Vulnerabilities

| # | Issue | Severity | Detail |
|---|---|---|---|
| 1 | Hardcoded API credentials | Critical | API key and secret stored as plain Base64 in APK bytecode |
| 2 | MD5 for HMAC signatures | High | MD5 is broken; signatures are forgeable |
| 3 | Cleartext HTTP allowed | High | `android:usesCleartextTraffic="true"` permits unencrypted traffic |
| 4 | Shared secret exposed | Critical | Anyone with the APK can forge valid API requests, impersonate any device, enumerate users |
| 5 | ANDROID_ID as device identity | Medium | Resets on factory reset / new Google account; not a reliable unique identifier |
| 6 | No certificate pinning | High | Despite anti-MITM measures, there is no cert pinning — a patched APK bypassing the VPN check could intercept all traffic |
| 7 | Anti-analysis evasion | Informational | Designed to obstruct security research — itself a red flag for malicious/grey-market apps |

---

## 8. Classification

| Attribute | Assessment |
|---|---|
| App type | Subscription signal/prediction overlay for Wingo gambling platform |
| Prank / harmless | No — connects to live backend, charges users (₹900+), serves real-time signals |
| Malware | Not in the traditional sense — no data exfiltration beyond device ID |
| Fraud risk | High — sells predictions for a gambling game; outcomes are random and cannot be predicted |
| Legal status | Likely violates Wingo/platform ToS; gambling signal tools are illegal or heavily regulated in most jurisdictions including India |
| Distribution | Sideloaded APK (not on Play Store) |

---

## 9. Recommendations

If you received this APK from someone:
- **Do not install it.** It requests overlay permission to draw over your screen while you use other apps.
- **Do not pay for access.** The signals it provides cannot predict random game outcomes — any apparent success is confirmation bias.
- **Report it** to Google Play Protect via the Play Store, and to the Wingo platform's abuse team.

If you are a security researcher:
- The API at `ubfuturetech.com` is live and can be reported to the domain registrar and hosting provider.
- The hardcoded credentials mean the entire user database can be accessed by anyone with this APK.

---

*Report generated by static analysis of decompiled APK bytecode using jadx 1.5.0.*
