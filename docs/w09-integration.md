# W09 — Homepage integration and acceptance (#26)

Verified on 2026-11-20 against main commit `f6a0b3786096083abf9940ff91d522b252d580e6`. This is the handoff for the remaining W09 integration work, not a second implementation of the public layout, visual design, or navigation.

## Existing delivery

| Requirement | Existing delivery on the baseline |
| --- | --- |
| World Cup landing page, edition banner, independent team entry and common layout | [#16](https://github.com/kleedaisuki/webdev-homework-2.0/pull/16) and [#21](https://github.com/kleedaisuki/webdev-homework-2.0/pull/21); `views/site/index.php`, `views/layouts/main.php`, `web/css/poster.css`. |
| Public match list, details and latest results | [#15](https://github.com/kleedaisuki/webdev-homework-2.0/pull/15) supplies list/detail; `models/WorldCupPortal.php` uses the shared published-fixture scope, filters to finished matches for the homepage, and selects three by descending `score_updated_at` then descending fixture ID. The public list itself remains in kickoff order. |
| Guestbook and downloads | [#25](https://github.com/kleedaisuki/webdev-homework-2.0/pull/25): homepage → 球迷讨论 → 写留言. [#24](https://github.com/kleedaisuki/webdev-homework-2.0/pull/24): 作业下载 in the common navigation. |
| Admin sign-in and the three maintenance entries | [#13](https://github.com/kleedaisuki/webdev-homework-2.0/pull/13) signs into the workbench. [#20](https://github.com/kleedaisuki/webdev-homework-2.0/pull/20), [#22](https://github.com/kleedaisuki/webdev-homework-2.0/pull/22) and [#28](https://github.com/kleedaisuki/webdev-homework-2.0/pull/28) deliver team editing, demo matches and moderation. [#40](https://github.com/kleedaisuki/webdev-homework-2.0/pull/40) connects the public preview links and mobile admin navigation; see [W17's route and label map](w17-validation.md). |

The shipped top-level labels are **世界杯专题** and **团队成员**, leading to 赛程赛果 and 我们的团队 respectively. The guestbook sits one click beyond the homepage's 球迷讨论 entry. `/team` and `/team/members/{id}` remain independent of World Cup national teams.

## Reproduce the acceptance run

Use a disposable MySQL database with all migrations applied, the default fixtures and team imported (`php yii seed/fixtures`, `php yii team-seed`), and an administrator created with `php yii account/create-admin`. Run the local site with the configuration and router in [W01](w01-validation.md). Keep a signed-out guest window separate from the signed-in admin window.

| Step | Observed result |
| --- | --- |
| At 390×844, start at `/`; click a 精选赛果 detail, use 团队成员 → 了解成员, then 主页 → 球迷讨论 → 写留言. | Match detail, `/team`, member detail and `/guestbook` all open via visible links. No horizontal page overflow on the home and guestbook pages. |
| From the homepage's 管理员登录, go to 管理工作台; open 团队资料管理, 留言审核 and 赛事管理. | All three modules open at 390×844. Workbench and module pages fit the viewport; the fixtures table scrolls inside its own container. |
| In 赛事管理, create demo A with kickoff `2026-06-01 08:00:00` Beijing time; pick the 2026 edition, two distinct teams and a venue. Save the demo as a draft, then save 已完赛 with `0:0` before publishing. | The finished draft is absent from 精选赛果 and the public 已完赛 list; a direct guest GET to its detail returns 404. |
| Click 发布, then reload the guest homepage; follow its match link, return to 赛程赛果, filter 已完赛 and open the same match from the list. | The same fixture shows `0 : 0` and its demo designation in all three views. Its detail explicitly says the result is not official. |
| Create demo B with kickoff `2026-06-02 08:00:00`, save `1:0` and publish. Let each score save occur in a different second. Finally change A to `2:1`. | The homepage shows only three results. B enters before A; editing A moves it above B even though A has the earlier kickoff. Home, list and detail all show A's new `2 : 1`. |
| Delete only demo A and B from their editors. | Temporary match records are removed; official fixtures are unchanged. |

Environment: Chromium 156, Linux, PHP 8.2.35, Yii 2.0.55 and MySQL 8.4.13. The existing `tests/w11-fixtures.php` HTTP journey also passed. No product code or visual changes were needed.
