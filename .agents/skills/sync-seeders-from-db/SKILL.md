---
name: sync-seeders-from-db
description: >-
  Safely compares the live MySQL database against DatabaseSeeder output using an
  in-memory SQLite connection, updates database/seeders/* and seeder tests to
  match current live data without mutating the live database, and verifies zero
  diffs across all application tables. Use when asked to sync/update seeders
  from current DB data or before running migrate:fresh --seed.
---

# Sync Seeders From Live Database

## Strict Safety Guardrail

- **NEVER run `migrate:fresh`, `db:seed`, or any write query against the live MySQL database** during comparison or sync.
- Perform all seeding verification on a dynamically configured in-memory SQLite connection (`seed_check`).

## Workflow

1. **Full-Table Diff Inspection (Read-Only on Live DB)**
   - Load all rows from the live `mysql` connection, excluding framework tables (`migrations`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `sessions`) and non-domain columns (`created_at`, `updated_at`, `remember_token`).
   - Configure a temporary `seed_check` SQLite `:memory:` connection (`foreign_key_constraints => true`), run `migrate` and `db:seed --class=DatabaseSeeder`, and compare all remaining application tables.
   - Normalize driver and non-semantic differences before diffing:
     - Date-only columns cast in SQLite: strip trailing ` 00:00:00` (`YYYY-MM-DD 00:00:00` -> `YYYY-MM-DD`).
     - Time columns: normalize `HH:MM` -> `HH:MM:SS`.
     - Decimal formatting: trim trailing fractional zeros (`10.00` -> `10`).
     - JSON columns: decode and re-encode with `JSON_UNESCAPED_UNICODE`; normalize `\r\n` -> `\n`.
     - `users.password`: verify plaintext match via `Hash::check()` instead of raw hash string comparison.
   - Use **natural keys** (instead of auto-increment `id`) for tables where UI deletions create ID gaps (notably `timetable_lanes` via `timetable_id:lane_id`, `timetable_lane_fixed_staff`, `timetable_tasks` via lane key + `title` + `start_time`, `task_staff_assignments`, and `lane_requests`).

2. **Drill Into Exact Column Diffs & Cross-References**
   - For every table showing a diff, inspect exact live values and check cross-seeder dependencies:
     - Renaming a master record (e.g. `LaneSeeder`) requires updating lookup names in downstream seeders (e.g. `TimetableSeeder`).
     - Updating `Timetable` fields in `TimetableSeeder` automatically propagates to `schedules` via `ScheduleRepositoryInterface::syncForTimetable()`.
     - Verify new columns written via `updateOrCreate()` exist in the target model's `#[Fillable]` / `$fillable`.

3. **Update Seeders & Seeder Tests**
   - Edit only the affected files under `database/seeders/`.
   - Search `tests/` for references to changed master names, counts, or `DatabaseSeeder`, and update affected `*SeederTest.php` files (adding targeted assertions for the newly synced state).

4. **Final Verification**
   - Run `vendor/bin/pint --dirty --format agent`.
   - Re-run the full-table live MySQL vs. `seed_check` SQLite comparison and confirm `TOTAL_DIFF_TABLES=0` across all application tables.
   - Run all seeder feature tests (`tests/Feature/*SeederTest.php`) and confirm 100% pass.
   - Clear view caches per project rules (`php artisan view:clear && rm -f storage/framework/views/*.php && chmod -R 777 storage/framework bootstrap/cache`).
