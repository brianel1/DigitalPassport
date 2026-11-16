"""Compatibility entry point for the current SQL snapshot exporter."""

from pathlib import Path
import shutil
import subprocess

root = Path(__file__).resolve().parents[1]
php = shutil.which("php")
if not php and Path("C:/xampp/php/php.exe").is_file():
    php = "C:/xampp/php/php.exe"
if not php:
    raise SystemExit("PHP was not found. Run php scripts\\export_schema_snapshot.php with PHP on PATH.")
raise SystemExit(subprocess.run([php, str(root / "scripts" / "export_schema_snapshot.php")], cwd=root).returncode)
