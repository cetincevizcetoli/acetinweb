#!/usr/bin/env python3
from __future__ import annotations

import sqlite3
import tempfile
from pathlib import Path

root = Path(__file__).resolve().parents[1]
schema_path = root / "app" / "schema.sql"

with tempfile.TemporaryDirectory(prefix="fikrimvar-schema-", ignore_cleanup_errors=True) as tmp:
    db_path = Path(tmp) / "empty.sqlite"
    with sqlite3.connect(db_path) as con:
        con.execute("PRAGMA foreign_keys=ON")
        con.executescript(schema_path.read_text(encoding="utf-8"))
        integrity = con.execute("PRAGMA integrity_check").fetchone()[0]
        foreign = con.execute("PRAGMA foreign_key_check").fetchall()

    print(f"Schema: {schema_path}")
    print(f"Test veritabanı: {db_path}")
    print(f"Integrity: {integrity}")
    print(f"Bozuk yabancı anahtar: {len(foreign)}")

    if integrity != "ok" or foreign:
        raise SystemExit(1)
