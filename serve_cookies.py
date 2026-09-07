"""
serve_cookies.py  —  Run on your SOURCE Windows PC
Exports cookies then serves them over LAN with a shared password.

Usage (one-shot):
    python serve_cookies.py --password mypass

Usage (auto-refresh every 30 minutes):
    python serve_cookies.py --password mypass --refresh 30

The receiver must use the same password.
"""

import argparse
import importlib.util
import json
import socket
import sys
import time
import threading
from http.server import BaseHTTPRequestHandler, HTTPServer
from pathlib import Path
from urllib.parse import urlparse, parse_qs

PORT = 9876
EXPORT_FILE = Path("cookies_export.json")

# Shared state — reset each round
_ready = threading.Event()   # set when export file is ready
_served = threading.Event()  # set once file is sent this round
_password: str = ""


# ── Export ────────────────────────────────────────────────────────────────────

def run_export():
    spec = importlib.util.spec_from_file_location(
        "export_cookies", Path(__file__).parent / "export_cookies.py"
    )
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    mod.main()


# ── LAN IP ────────────────────────────────────────────────────────────────────

def get_lan_ip() -> str:
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return "127.0.0.1"


# ── HTTP handler ──────────────────────────────────────────────────────────────

class Handler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        pass

    def do_GET(self):
        parsed = urlparse(self.path)
        qs = parse_qs(parsed.query)
        provided = qs.get("password", [""])[0]

        if parsed.path != "/cookies" or provided != _password:
            self.send_response(403)
            self.end_headers()
            self.wfile.write(b"Wrong password.")
            print(f"  [!] Rejected {self.client_address[0]} (bad password)")
            return

        # Wait up to 30 s for the export to finish (handles race on startup)
        if not _ready.wait(timeout=30):
            self.send_response(503)
            self.end_headers()
            self.wfile.write(b"Export not ready yet, try again in a moment.")
            return

        data = EXPORT_FILE.read_bytes()
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)

        print(f"  [OK] Cookies sent to {self.client_address[0]}")
        _served.set()


# ── One round: export → serve → (optionally) wait ─────────────────────────────

def run_round(lan_ip: str, server: HTTPServer, refresh_mins: int, round_num: int):
    global _ready, _served

    _ready = threading.Event()
    _served = threading.Event()

    banner = f"Round {round_num}" if refresh_mins else "Transfer"
    print(f"\n{'=' * 55}")
    print(f"  {banner}  —  exporting cookies...")
    print(f"{'=' * 55}")

    run_export()
    _ready.set()

    print(f"\n  Listening on:  http://{lan_ip}:{PORT}")
    print(f"  Password:      {_password}")
    if refresh_mins:
        print(f"  Auto-refresh:  every {refresh_mins} min")
    print(f"\n  On your SECOND PC run:")
    cmd = f"python receive_cookies.py {lan_ip} --password {_password}"
    if refresh_mins:
        cmd += f" --refresh {refresh_mins}"
    print(f"    {cmd}")
    print(f"\n  Waiting for receiver... (Ctrl-C to stop)\n")

    if refresh_mins:
        # In refresh mode: serve the file to as many requests as come in
        # during this window, then move on to the next round.
        _served.wait(timeout=refresh_mins * 60)
    else:
        # One-shot: wait until served, then shut down.
        _served.wait()
        t = threading.Thread(target=server.shutdown, daemon=True)
        t.start()


# ── Main ──────────────────────────────────────────────────────────────────────

def main():
    global _password

    parser = argparse.ArgumentParser(description="Serve browser cookies over LAN")
    parser.add_argument("--password", required=True, help="Shared password (same on both PCs)")
    parser.add_argument(
        "--refresh",
        type=int,
        default=0,
        metavar="MINUTES",
        help="Re-export and re-serve every N minutes (omit for one-shot)",
    )
    args = parser.parse_args()

    _password = args.password
    refresh_mins = args.refresh

    lan_ip = get_lan_ip()
    server = HTTPServer((lan_ip, PORT), Handler)
    server_thread = threading.Thread(target=server.serve_forever, daemon=True)
    server_thread.start()

    round_num = 1
    try:
        while True:
            run_round(lan_ip, server, refresh_mins, round_num)
            if not refresh_mins:
                break
            print(f"\n  Next refresh in {refresh_mins} min — press Ctrl-C to stop.")
            time.sleep(refresh_mins * 60)
            round_num += 1
    except KeyboardInterrupt:
        print("\n\nStopped.")
    finally:
        server.shutdown()


if __name__ == "__main__":
    main()
