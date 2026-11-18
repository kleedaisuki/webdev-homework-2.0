# W12: Guestbook moderation

[Issue #27](https://github.com/kleedaisuki/webdev-homework-2.0/issues/27) closes the W08 guestbook loop. Run `php yii migrate` before deploying the code. W12 creates `guest_review` without rebuilding `guest_message`; existing pending messages appear in the queue. If there is no administrator yet, run `php yii account/create-admin`.

## Acceptance walkthrough

Use separate browser sessions for a visitor and an administrator.

1. Submit two messages at `/guestbook`, e.g. “最期待哪场比赛？” and “精华视频什么时候更新？”. Both get a pending receipt; neither appears on `/guestbook` or `/world-cup/fans`.
2. Sign in at `/admin/login`. From the workbench, open **留言审核**. Approve the first message and reject the second, optionally leaving an internal note.
3. Both leave the pending queue. The **已通过** and **已拒绝** tabs show the corresponding messages; only the approved one is visible on either public page. For later demos (W14/W15/W21), leave the two examples in these states.
4. Check the results and administrator identity in the database. The note is not part of either public projection.

   ```sql
   SELECT r.message_id, m.nickname, m.status, r.decision, a.username, r.created_at AS reviewed_at_utc
   FROM guest_review AS r
   JOIN guest_message AS m ON m.id = r.message_id
   JOIN account AS a ON a.id = r.reviewer_id
   WHERE r.message_id IN (/* replace with the two demo message IDs */)
   ORDER BY r.message_id, r.created_at, r.id;
   ```

The history tabs allow changing a previous decision. Repeating the current decision does not add an audit row. Public visibility always follows the current message status, not past approvals.

## Implementation and verification

- `ReviewForm` only loads the decision and optional note; the target comes from the route, and the reviewer from the logged-in account. The existing admin access filter and CSRF configuration cover both endpoints.
- `GuestReview::record()` uses the message primary key for an InnoDB [locking read](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html). Requests reviewing the same message compare their decision under the same row lock. The audit insert and status update share a [Yii transaction](https://www.yiiframework.com/doc/guide/2.0/en/db-dao#performing-transactions); the existing W08 approved-only queries do not change.
- W12's down migration drops the audit table, not the messages. It does not reverse decisions already applied to W08 messages; back up the audit table before reverting an environment with review history.

Verified on PHP 8.2.35, Yii 2.0.55, MySQL 8.4.13:

```sh
php tests/w12-guestbook-review.php http://127.0.0.1:8080
php tests/w08-guestbook.php http://127.0.0.1:8080
```

- W12: 33 checks against real HTTP and MySQL, including cross-page visibility, stored reviewer, invalid forms, duplicate and changed decisions, and a review attempt after logout. The script removes its own temporary account and messages.
- W08: 32 regression checks. On an isolated database, the W12 migration round-trip preserved a pre-existing pending message. A temporary MySQL trigger forced the message update to fail after the audit insert; the message stayed pending and no audit row remained.
- Chromium, 390px viewport: submitted two messages as a visitor and moderated them in a separate administrator session. Both public pages agreed; the review queue wrapped a long unbroken message without horizontal overflow.
