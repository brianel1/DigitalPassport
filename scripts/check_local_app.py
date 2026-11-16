"""Check the running local API with the provisioned administrator account."""

import json
from pathlib import Path
from urllib.request import Request, urlopen

env = {}
for line in (Path(__file__).resolve().parents[1] / "backend" / ".env").read_text(encoding="utf-8").splitlines():
    if "=" in line and not line.startswith("#"):
        key, value = line.split("=", 1)
        env[key] = value.strip('"')

base = "http://127.0.0.1:8000"
def request(path, data=None, token=None):
    payload = json.dumps(data).encode() if data is not None else None
    headers = {"Accept": "application/json"}
    if payload is not None:
        headers["Content-Type"] = "application/json"
    if token:
        headers["Authorization"] = f"Bearer {token}"
    with urlopen(Request(base + path, data=payload, headers=headers), timeout=20) as response:
        return json.load(response)

session = request("/api/auth/login", {"email": env["ADMIN_EMAIL"], "password": env["ADMIN_PASSWORD"]})
try:
    stats = request("/api/admin/dashboard-stats", token=session["token"])
    print(f"Local API login OK; students={stats['totalStudents']}, pending reviews={stats['pendingReviews']}")
finally:
    request("/api/auth/logout", {}, token=session["token"])
