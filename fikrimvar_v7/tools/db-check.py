#!/usr/bin/env python3
from __future__ import annotations
import sqlite3
from pathlib import Path

root = Path(__file__).resolve().parents[1]
db_path = root / "storage" / "fikrimvar.sqlite"
if not db_path.exists():
    raise SystemExit(f"Veritabanı bulunamadı: {db_path}")

with sqlite3.connect(db_path) as con:
    integrity = con.execute("PRAGMA integrity_check").fetchone()[0]
    foreign = con.execute("PRAGMA foreign_key_check").fetchall()
    print(f"Veritabanı: {db_path}")
    print(f"Integrity: {integrity}")
    print(f"Bozuk yabancı anahtar: {len(foreign)}")
    for table in [
        "projects", "updates", "stories", "story_sections",
        "story_section_items", "media", "links", "notes", "admin_users"
    ]:
        count = con.execute(f"SELECT COUNT(*) FROM {table}").fetchone()[0]
        print(f"{table}: {count}")
