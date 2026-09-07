"""
receive_cookies.py  —  Run on your TARGET Windows PC
Pulls cookies from serve_cookies.py running on your source PC (LAN only),
then imports them into Chrome, Edge, and Firefox.

Usage:
    python receive_cookies.py <SOURCE_IP> <PIN>

Example:
    python receive_cookies.py 192.168.1.42 A3F9C12B

Requirements (install once):
    pip install pycryptodome pywin32

IMPORTANT: Close all browser windows before running.
"""

import sys
import json
import socket
import urllib.request
import urllib.error
from pathlib import Path


PORT = 9876


# ── LAN safety check ──────────────────────────────────────────────────────────

PRIVATE_RANGES = [
    ("10.0.0.0",     "10.255.255.255"),
    ("172.16.0.0",   "172.31.255.255"),
    ("192.168.0.0",  "192.168.255.255"),
    ("127.0.0.0",    "127.255.255.255"),
]


def _ip_to_int(ip: str) -> int:
    parts = [int(x) for x in ip.split(".")]
    return (parts[0] << 24) | (parts[1] << 16) | (parts[2] << 8) | parts[3]


def is_private_ip(ip: str) -> bool:
    try:
        n = _ip_to_int(ip)
        for lo, hi in PRIVATE_RANGES:
            if _ip_to_int(lo) <= n <= _ip_to_int(hi):
                return True
        return False
    except Exception:
        return False


# ── Fetch ─────────────────────────────────────────────────────────────────────

def fetch_cookies(source_ip: str, pin: str) -> list:
    if not is_private_ip(source_ip):
        print(f"ERROR: {source_ip} is not a private/LAN address.")
        print("This tool only works on your local network.")
        sys.exit(1)

    url = f"http://{source_ip}:{PORT}/cookies?pin={pin}"
    print(f"Connecting to {source_ip}:{PORT} ...")

    try:
        with urllib.request.urlopen(url, timeout=10) as resp:
            data = resp.read()
    except urllib.error.HTTPError as e:
        if e.code == 403:
            print("ERROR: Wrong PIN — double-check what serve_cookies.py printed.")
        elif e.code == 410:
            print("ERROR: File already served. Re-run serve_cookies.py on the source PC.")
        else:
            print(f"ERROR: HTTP {e.code}")
        sys.exit(1)
    except urllib.error.URLError as e:
        print(f"ERROR: Could not reach {source_ip}:{PORT} — {e.reason}")
        print("Make sure serve_cookies.py is running on the source PC.")
        sys.exit(1)

    cookies = json.loads(data)
    print(f"Received {len(cookies)} cookies from {source_ip}.\n")
    return cookies


# ── Main ──────────────────────────────────────────────────────────────────────

def main():
    print("=" * 55)
    print("  COOKIE RECEIVER  —  LAN Transfer Mode")
    print("=" * 55)

    if len(sys.argv) < 3:
        print("\nUsage: python receive_cookies.py <SOURCE_IP> <PIN>")
        print("  SOURCE_IP  — shown by serve_cookies.py on the source PC")
        print("  PIN        — 8-character code shown by serve_cookies.py")
        sys.exit(1)

    source_ip = sys.argv[1]
    pin = sys.argv[2].strip().upper()

    cookies = fetch_cookies(source_ip, pin)

    # Save locally then call the importer
    export_file = Path("cookies_export.json")
    with open(export_file, "w", encoding="utf-8") as f:
        json.dump(cookies, f, indent=2, ensure_ascii=False)

    print("Running import...\n")

    import importlib.util
    spec = importlib.util.spec_from_file_location(
        "import_cookies", Path(__file__).parent / "import_cookies.py"
    )
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    mod.main()


if __name__ == "__main__":
    main()
