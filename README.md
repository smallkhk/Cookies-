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

### On your SOURCE PC

1. **Close all browser windows** (Chrome, Edge, Firefox).
2. Run:
   ```
   python export_cookies.py
   ```
3. This creates `cookies_export.json` in the same folder.
4. Copy `cookies_export.json` to your second PC (USB drive, shared folder, etc.).

---

### On your TARGET (second) PC

1. **Close all browser windows**.
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
