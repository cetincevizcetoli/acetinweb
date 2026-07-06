#!/usr/bin/env python3
from __future__ import annotations
import sqlite3
import subprocess
from pathlib import Path

root = Path(__file__).resolve().parents[1]
config_path = root / "config" / "config.php"
result = subprocess.run(
    ["php", "-r", f"require {str(config_path)!r}; echo FV7_DB;"],
    check=True,
    capture_output=True,
    text=True,
)
db_path = Path(result.stdout.strip())
if not db_path.exists():
    raise SystemExit(f"Veritabanı bulunamadı: {db_path}")

db_uri = db_path.resolve().as_posix()
with sqlite3.connect(f"file:{db_uri}?mode=ro", uri=True) as con:
    integrity = con.execute("PRAGMA integrity_check").fetchone()[0]
    foreign = con.execute("PRAGMA foreign_key_check").fetchall()
    print(f"Veritabanı: {db_path}")
    print(f"Integrity: {integrity}")
    print(f"Bozuk yabancı anahtar: {len(foreign)}")
    for table in [
        "projects", "updates", "stories", "story_parts", "story_sections",
        "story_section_items", "media", "links", "notes", "admin_users"
    ]:
        count = con.execute(f"SELECT COUNT(*) FROM {table}").fetchone()[0]
        print(f"{table}: {count}")
