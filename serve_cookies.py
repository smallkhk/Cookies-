"""
serve_cookies.py  —  Run on your SOURCE Windows PC
Exports cookies then serves them once over LAN with a one-time PIN.

Usage:
    python serve_cookies.py
    -> shows your LAN IP + PIN; run receive_cookies.py on the second PC
"""

import os
import json
import secrets
import socket
import threading
import webbrowser
from http.server import BaseHTTPRequestHandler, HTTPServer
from pathlib import Path
from urllib.parse import urlparse, parse_qs

# ── Run the export first ───────────────────────────────────────────────────────

def run_export():
    print("=" * 55)
    print("  COOKIE EXPORTER  —  LAN Transfer Mode")
    print("=" * 55)

    # Lazy-import the export logic from export_cookies.py
    import importlib.util, sys
    spec = importlib.util.spec_from_file_location(
        "export_cookies", Path(__file__).parent / "export_cookies.py"
    )
    mod = importlib.util.load_from_spec(spec)
    spec.loader.exec_module(mod)
    mod.main()


# ── Discover LAN IP ────────────────────────────────────────────────────────────

def get_lan_ip() -> str:
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return "127.0.0.1"


# ── One-shot HTTP server ───────────────────────────────────────────────────────

SERVED = threading.Event()
PIN: str = ""
EXPORT_FILE = Path("cookies_export.json")


class Handler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        pass  # suppress default access log; we print our own

    def do_GET(self):
        parsed = urlparse(self.path)
        qs = parse_qs(parsed.query)
        provided_pin = qs.get("pin", [""])[0]

        if parsed.path != "/cookies" or provided_pin != PIN:
            self.send_response(403)
            self.end_headers()
            self.wfile.write(b"Wrong PIN or path.")
            client = self.client_address[0]
            print(f"  [!] Rejected connection from {client} (bad PIN)")
            return

        if SERVED.is_set():
            self.send_response(410)
            self.end_headers()
            self.wfile.write(b"File already served. Start again to transfer again.")
            return

        data = EXPORT_FILE.read_bytes()
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)

        client = self.client_address[0]
        print(f"\n  [OK] File sent to {client}")
        print("  Server shutting down — transfer complete.")
        SERVED.set()

        # Shut down server after response is sent
        t = threading.Thread(target=self.server.shutdown, daemon=True)
        t.start()


def serve(port: int = 9876) -> None:
    lan_ip = get_lan_ip()
    server = HTTPServer((lan_ip, port), Handler)

    print("\n" + "=" * 55)
    print(f"  Listening on:  http://{lan_ip}:{port}")
    print(f"  One-time PIN:  {PIN}")
    print("=" * 55)
    print("\n  On your SECOND PC run:")
    print(f"    python receive_cookies.py {lan_ip} {PIN}")
    print("\n  Waiting for connection... (Ctrl-C to cancel)\n")

    server.serve_forever()


# ── Main ───────────────────────────────────────────────────────────────────────

def main():
    global PIN
    PIN = secrets.token_hex(4).upper()  # e.g. "A3F9C12B"

    run_export()
    serve()

    print("\nDone.")


if __name__ == "__main__":
    main()
