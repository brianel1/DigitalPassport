"""Configure a clean local database and local AI without exposing credentials."""

from pathlib import Path
import secrets

env_path = Path(__file__).resolve().parents[1] / "backend" / ".env"
if not env_path.exists():
    raise SystemExit("Copy backend/.env.example to backend/.env first")

lines = env_path.read_text(encoding="utf-8").splitlines()
settings = {
    "DB_DATABASE": "digitalpassport_live",
    "OLLAMA_MODEL": "qwen3:4b",
    "OLLAMA_CHAT_MODEL": "qwen3.5:4b",
    "ADMIN_NAME": '"Local Administrator"',
    "ADMIN_EMAIL": "admin@digitalpassport.local",
}
existing = {
    line.split("=", 1)[0]: line.split("=", 1)[1]
    for line in lines if "=" in line and not line.startswith("#")
}
settings["ADMIN_PASSWORD"] = existing.get("ADMIN_PASSWORD") or secrets.token_urlsafe(20)
for key in ("ADMIN_EMAIL", "ADMIN_NAME", "DB_DATABASE", "OLLAMA_MODEL", "OLLAMA_CHAT_MODEL"):
    settings[key] = existing.get(key) or settings[key]

updated = []
seen = set()
for line in lines:
    key = line.split("=", 1)[0] if "=" in line else ""
    if key in settings:
        if key not in seen:
            updated.append(f"{key}={settings[key]}")
            seen.add(key)
    else:
        updated.append(line)
for key, value in settings.items():
    if key not in seen:
        updated.append(f"{key}={value}")
env_path.write_text("\n".join(updated) + "\n", encoding="utf-8")
print("Configured clean local DB, Ollama model and administrator in backend/.env")
