---
name: PostgreSQL lastInsertId
description: PDO::lastInsertId() needs sequence name for PostgreSQL
---
In PDO + PostgreSQL, `$pdo->lastInsertId()` without args returns empty/wrong value.
Fix: `$pdo->lastInsertId('orders_id_seq')` — MySQL ignores the string arg; PostgreSQL uses it.

**Sequence naming:** PostgreSQL auto-creates sequences as `tablename_columnname_seq` for SERIAL columns. orders.id → orders_id_seq.

**How to apply:** Any INSERT into orders (or other SERIAL tables) must use `lastInsertId('tablename_id_seq')`.
