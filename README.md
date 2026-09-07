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

#### One-shot

**Source PC:**
1. Close all browser windows.
2. Run:
   ```
   python serve_cookies.py --password mypass
   ```
3. It prints your LAN IP and the command to run on PC 2, e.g.:
   ```
   Listening on:  http://192.168.1.42:9876
   Password:      mypass

   On your SECOND PC run:
     python receive_cookies.py 192.168.1.42 --password mypass
   ```

**Target PC:**
1. Close all browser windows.
2. Run the command shown on the source PC:
   ```
   python receive_cookies.py 192.168.1.42 --password mypass
   ```
3. Open your browsers — you should be logged in everywhere.

---

#### Auto-refresh (keeps cookies in sync automatically)

Run both with `--refresh <minutes>` using the **same interval**:

**Source PC:**
```
python serve_cookies.py --password mypass --refresh 30
```

**Target PC:**
```
python receive_cookies.py 192.168.1.42 --password mypass --refresh 30
```

Both loop indefinitely. Every 30 minutes the source re-exports fresh cookies and the receiver re-imports them. Press `Ctrl-C` on either side to stop.

> Only reachable on your local network — the receiver rejects any non-private IP.

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
