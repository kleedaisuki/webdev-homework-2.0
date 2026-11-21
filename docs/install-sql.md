# W18: Restore the site from install.sql

For a fresh installation, import [`data/install.sql`](../data/install.sql) into a new, empty MySQL 8.4 database. The dump contains the 11 business tables and Yii's `migration` table, with the migration history already recorded. It includes the repository's fixture and team seeds, but no accounts, password hashes, sessions, user-submitted messages, or review records. Each installation creates its own administrator.

## Restore and start

1. From the repository root, install the locked dependencies and generate local configuration:

   ```sh
   composer install --prefer-dist
   php init.php
   ```

2. In MySQL, create an empty database. Use a local MySQL account with privileges to create tables and read/write this database for the import. Set its connection details and the same database name in `config/db.local.php`.

   ```sql
   CREATE DATABASE world_cup_dev
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Import from the repository root. Replace `world_cup_user` with your MySQL username and adjust the host/port if needed. The client prompts for your MySQL password.

   **Bash / POSIX shell:**

   ```sh
   mysql -h 127.0.0.1 -P 3306 -u world_cup_user -p --default-character-set=utf8mb4 world_cup_dev < data/install.sql
   ```

   **Windows PowerShell:** Start the client using the same command **without** `< data/install.sql`, then run this at the `mysql>` prompt:

   ```sql
   SOURCE data/install.sql
   ```

   If an import fails partway through, correct the error and retry in another empty database; do not retry over partially created tables. See the [MySQL import guide](https://dev.mysql.com/doc/refman/8.4/en/reloading-sql-format-dumps.html).

4. Create an administrator interactively, then run the site:

   ```sh
   php yii environment/check
   php yii account/create-admin
   php yii serve 127.0.0.1 --port=8080
   ```

   At `http://127.0.0.1:8080`, open `/world-cup/matches/1` to see Mexico 2–0 South Africa and the FIFA source link. Open `/team` to see four seed members, then sign in at `/admin/login`. The classroom match is visible as a draft under `/admin/matches`, not on the public schedule or at `/world-cup/matches/5`.

**Alternative for a fresh database:** Instead of importing SQL, run `php yii migrate --interactive=0`, `php yii seed/fixtures`, and `php yii team-seed` in that order before step 4. Choose one installation path per database; the SQL already contains the schema, migration history, and seed rows.

## Regenerate the dump

Use a disposable, empty database created with the same charset and collation. Point Yii to it, run the three migration/seed commands above, and export **before** creating an administrator or opening the database to users. Run from the repository root (POSIX shell example):

```sh
mkdir -p .temp
mysqldump -h 127.0.0.1 -P 3306 -u world_cup_user -p \
  --default-character-set=utf8mb4 --single-transaction --set-gtid-purged=OFF \
  --no-tablespaces --skip-add-drop-table --skip-add-locks --skip-comments \
  --order-by-primary world_cup_dev > .temp/install.sql
```

Use your disposable database name in place of `world_cup_dev`. The dump does not create a database, select a database, or require global replication settings. Keep the header from the tracked file, verify the replacement in a different empty database, and update the tracked file only after the restore passes.

## Verified restore

On 2026-11-21, using MySQL Community Server 8.4.13, PHP 8.2.35 and Yii 2.0.55 on Linux, the dump generated from `05d7876` was imported into a separate empty database with no migrations or seed commands run against the restored database. Import through the MySQL client's `SOURCE` command also worked in a third empty database.

| Check | Result |
| --- | --- |
| W14 [design dictionary](https://github.com/kleedaisuki/webdev-homework-2.0/issues/30) | 11 business tables and 9 foreign keys; the separate Yii history table records 13 migrations plus its baseline. |
| Fixtures and relationships | 104 published official fixtures, all with source URLs and verification timestamps; 1 unpublished classroom draft. The Mexico–South Africa fixture joins both teams and the team page joins all four members. |
| Recovery as a user | Public schedule, sourced match detail, team page, and member page loaded. The draft returned HTTP 404 publicly and opened for editing after signing in with a newly CLI-created administrator. |
