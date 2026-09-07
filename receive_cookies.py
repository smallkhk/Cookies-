"""
receive_cookies.py  —  Run on your TARGET Windows PC
Pulls cookies from serve_cookies.py running on your source PC (LAN only),
then imports them into Chrome, Edge, and Firefox.

Usage (one-shot):
    python receive_cookies.py <SOURCE_IP> --password mypass

Usage (auto-refresh, same interval as source):
    python receive_cookies.py <SOURCE_IP> --password mypass --refresh 30

Requirements (install once):
    pip install pycryptodome pywin32

IMPORTANT: Close all browser windows before running, or use --refresh
           (the script will remind you before each import).
"""

import argparse
import importlib.util
import json
import sys
import time
import urllib.request
import urllib.error
from datetime import datetime
from pathlib import Path

PORT = 9876


# ── LAN safety check ──────────────────────────────────────────────────────────

PRIVATE_RANGES = [
    ("10.0.0.0",    "10.255.255.255"),
    ("172.16.0.0",  "172.31.255.255"),
    ("192.168.0.0", "192.168.255.255"),
    ("127.0.0.0",   "127.255.255.255"),
]


def _ip_to_int(ip: str) -> int:
    parts = [int(x) for x in ip.split(".")]
    return (parts[0] << 24) | (parts[1] << 16) | (parts[2] << 8) | parts[3]


def is_private_ip(ip: str) -> bool:
    try:
        n = _ip_to_int(ip)
        return any(_ip_to_int(lo) <= n <= _ip_to_int(hi) for lo, hi in PRIVATE_RANGES)
    except Exception:
        return False


# ── Fetch ─────────────────────────────────────────────────────────────────────

def fetch_cookies(source_ip: str, password: str) -> list | None:
    """Returns cookie list on success, None on retriable error, exits on fatal error."""
    if not is_private_ip(source_ip):
        print(f"ERROR: {source_ip} is not a private/LAN address.")
        print("This tool only works on your local network.")
        sys.exit(1)

    url = f"http://{source_ip}:{PORT}/cookies?password={password}"
    print(f"  Connecting to {source_ip}:{PORT} ...")

    try:
        with urllib.request.urlopen(url, timeout=15) as resp:
            data = resp.read()
        cookies = json.loads(data)
        print(f"  Received {len(cookies)} cookies.")
        return cookies
    except urllib.error.HTTPError as e:
        if e.code == 403:
            print("ERROR: Wrong password — must match --password on serve_cookies.py.")
            sys.exit(1)
        elif e.code == 503:
            print("  Source is still exporting, retrying in 5 s...")
            return None
        else:
            print(f"  HTTP {e.code} — will retry next round.")
            return None
    except urllib.error.URLError as e:
        print(f"  Could not reach source ({e.reason}) — will retry next round.")
        return None


# ── Import ────────────────────────────────────────────────────────────────────

def run_import(cookies: list):
    export_file = Path("cookies_export.json")
    with open(export_file, "w", encoding="utf-8") as f:
        json.dump(cookies, f, indent=2, ensure_ascii=False)

    spec = importlib.util.spec_from_file_location(
        "import_cookies", Path(__file__).parent / "import_cookies.py"
    )
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    mod.main()


# ── One round ─────────────────────────────────────────────────────────────────

def run_round(source_ip: str, password: str, round_num: int, refresh_mins: int):
    ts = datetime.now().strftime("%H:%M:%S")
    banner = f"Round {round_num}  [{ts}]" if refresh_mins else "Transfer"
    print(f"\n{'=' * 55}")
    print(f"  {banner}")
    print(f"{'=' * 55}")

    if refresh_mins:
        print("  Close your browsers now if you want sessions to refresh cleanly.")
        print("  (You have 10 seconds — press Enter to skip the wait)")
        try:
            import msvcrt, select
            waited = 0
            while waited < 10:
                if msvcrt.kbhit():
                    msvcrt.getch()
                    break
                time.sleep(1)
                waited += 1
        except Exception:
            time.sleep(3)

    # Retry loop for 503 (source still exporting)
    cookies = None
    for attempt in range(6):
        cookies = fetch_cookies(source_ip, password)
        if cookies is not None:
            break
        if attempt < 5:
            time.sleep(5)

    if cookies is None:
        print("  Could not fetch cookies this round — will try again next refresh.")
        return

    print()
    run_import(cookies)


# ── Main ──────────────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(description="Receive browser cookies over LAN")
    parser.add_argument("source_ip", help="LAN IP of the source PC")
    parser.add_argument("--password", required=True, help="Shared password (must match source)")
    parser.add_argument(
        "--refresh",
        type=int,
        default=0,
        metavar="MINUTES",
        help="Re-fetch and re-import every N minutes (omit for one-shot)",
    )
    args = parser.parse_args()

    print("=" * 55)
    print("  COOKIE RECEIVER  —  LAN Transfer Mode")
    print("=" * 55)

    round_num = 1
    try:
        while True:
            run_round(args.source_ip, args.password, round_num, args.refresh)
            if not args.refresh:
                break
            print(f"\n  Next refresh in {args.refresh} min — press Ctrl-C to stop.")
            time.sleep(args.refresh * 60)
            round_num += 1
    except KeyboardInterrupt:
        print("\n\nStopped.")

    print("\nDone.")


if __name__ == "__main__":
    main()
