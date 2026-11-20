# W20 — Teaching-assistant showcase handoff

The [eight-slide presentation](../data/team/星河工坊_项目展示%282412712_2410824_2410868_2413174%29.pdf) is the classroom handout and presentation. It is listed under **作业下载 → 团队作业**; no application changes are needed. Use fullscreen PDF mode, with two local browser windows ready (guest and administrator).

| Slide | Presenter cue |
| --- | --- |
| 1–3 | Explain why the World Cup and the course team have separate public entry points but share one application. Point out the 11 business tables and the difference between 104 FIFA-sourced records and the one seeded demo. |
| 4 | In the guest window, open a non-demo result from **精选赛果** and show its **FIFA 官方来源** link. In the admin window, choose **管理工作台 → 赛事管理 → 新建演示比赛**, select a tournament, different teams and a venue, and enter `2026-06-01 08:00:00` Beijing time. Save the draft, publish it, then record **已完赛, 0:0**. Refresh the guest homepage and open that demo. Both surfaces should show **0 : 0** and **演示数据**. |
| 5 | Keep the first member's public detail page open, reached via **团队成员**. In the admin window choose **管理工作台 → 团队资料管理 → 编辑成员** for the same member. Save a one-line change to **个人简介** and refresh the guest detail page. Keep the original text for restoration. |
| 6–8 | Use the linked PRs when asked about contributions. Finish through **作业下载** by opening this PDF. Restore the original member bio and delete only the match created for this run. |

Use a local seeded demo database, not shared data. Slide 8 gives the setup sequence; [W01](w01-validation.md) covers local configuration and [W17](w17-validation.md) gives the actual navigation labels and URLs. The front-end screenshots show seeded placeholder profiles; the four names on slides 6–7 come from the [requirements roster](requirements.md#62-分工), not from those placeholders.

## Evidence and rehearsal

- Site and screenshots: main baseline `f6a0b3786096083abf9940ff91d522b252d580e6`; Chromium, PHP 8.2.35, Yii 2.0.55, MySQL 8.4.13. Both slide routes were rehearsed in separate browser sessions: official source link present; the demo appeared with `0:0` after publishing and recording the score; the saved bio appeared on the same public member detail. The demo was deleted and the bio restored afterward.
- Architecture: [W14 design PR #39](https://github.com/kleedaisuki/webdev-homework-2.0/pull/39) checked against the migrations. Coverage: [fixture expansion PR #29](https://github.com/kleedaisuki/webdev-homework-2.0/pull/29) checked against the two current CSVs and a clean seed run.
- Contributions: the linked merged PRs and changed files distinguish the planned owner from the actual contributor. In particular, Clash727 submitted public guestbook and draw expansion (#25/#29); kleedaisuki submitted W04 samples, coursework downloads and moderation (#7/#24/#28). The [W19 implementation document](https://github.com/kleedaisuki/webdev-homework-2.0/issues/34) is not merged at this baseline; compare its eventual file attribution to the same PR evidence. [W16 personal ZIPs](https://github.com/kleedaisuki/webdev-homework-2.0/issues/14) are not counted as delivered.
- Artifact check: rendered the 8 pages at 16:9 and checked the embedded PR links. A Chromium download via **首页 → 作业下载 → 团队作业** retained the expected filename and matched the committed PDF byte for byte; the downloaded copy opened as an eight-page PDF.
