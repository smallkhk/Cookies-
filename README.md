# Browser Cookie Transfer Scripts

Transfer your own browser cookies (Chrome, Edge, Firefox) from one Windows PC to another, keeping all your sessions active.

## Requirements

Install once on **both** PCs:

```
pip install pycryptodome pywin32
```

Python 3.8+ required.

---

## Steps

### Option A — LAN Transfer (both PCs on same Wi-Fi/router)

**Source PC:**
1. Close all browser windows.
2. Run:
   ```
   python serve_cookies.py
   ```
3. It exports cookies, then prints your LAN IP and a one-time PIN, e.g.:
   ```
   Listening on:  http://192.168.1.42:9876
   One-time PIN:  A3F9C12B

   On your SECOND PC run:
     python receive_cookies.py 192.168.1.42 A3F9C12B
   ```
4. Leave it running until the transfer completes — it shuts itself down automatically.

**Target PC:**
1. Close all browser windows.
2. Run the command shown on the source PC:
   ```
   python receive_cookies.py 192.168.1.42 A3F9C12B
   ```
3. Open your browsers — you should be logged in everywhere.

> The server only accepts one request (one-time PIN), then shuts down. Only reachable on your local network.

---

### Option B — Manual (USB / shared folder)

**Source PC:**
1. Close all browser windows.
2. Run:
   ```
   python export_cookies.py
   ```
3. Copy `cookies_export.json` to your second PC.

**Target PC:**
1. Close all browser windows.
2. Put `cookies_export.json` and `import_cookies.py` in the same folder.
3. Run:
   ```
   python import_cookies.py
   ```
4. Open your browsers — you should be logged in everywhere.

> **Backup**: the scripts automatically back up existing cookie files as `Cookies.bak` (Chrome/Edge) and `cookies.sqlite.bak` (Firefox) before overwriting. Restore those if anything goes wrong.

---

## How it works

| Browser | Storage | Transfer method |
|---------|---------|-----------------|
| Chrome | Encrypted SQLite (DPAPI + AES-256-GCM) | Decrypt on source → re-encrypt with new key on target |
| Edge | Same as Chrome | Same |
| Firefox | Plain SQLite (`cookies.sqlite`) | Direct copy / reimport |

Chrome and Edge encrypt cookie values with a machine-specific key. The export script decrypts them on your source machine; the import script generates a new key on the target and re-encrypts them there.

---

## Notes

- Session cookies (no expiry) may not survive — browsers often don't persist those to disk.
- Some sites (Google, banks) bind sessions to IP or device fingerprint and may log you out regardless.
- Run as your normal user account, not Administrator.
