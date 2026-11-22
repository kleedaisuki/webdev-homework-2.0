# 世界杯专题站 · 实现文档

**星河工坊｜互联网数据库开发课程设计｜W19｜2026-11-22**

2412712 王子楸 · 2410824 王艺霖 · 2410868 孙锘梵 · 2413174 郭嘉睿

## 01｜从工作单到可运行站点

[W19 #34](https://github.com/kleedaisuki/webdev-homework-2.0/issues/34) 要求按实际合并证据说明实现过程。本文以 [`bf36e97`](https://github.com/kleedaisuki/webdev-homework-2.0/commit/bf36e97)（11 月 18 日收口的 D4 核心功能）为实现基线；定稿前核对的 `main` 为 [`cf8bf60`](https://github.com/kleedaisuki/webdev-homework-2.0/commit/cf8bf60)。下表按可追溯的提交及合并里程碑整理，不把计划日期或工时当作事实。

| 进展 | 实际实现和合并证据 |
| --- | --- |
| 11 月 13 日 · 地基 | W02 的表、路由与共享契约 [#5](https://github.com/kleedaisuki/webdev-homework-2.0/pull/5)，W01 的 Yii2 Basic 骨架 [#6](https://github.com/kleedaisuki/webdev-homework-2.0/pull/6)，W04 的线框与初始赛事样本 [#7](https://github.com/kleedaisuki/webdev-homework-2.0/pull/7)。 |
| 11 月 15–16 日 · 先读后写 | W05 的管理员登录和后台入口 [#13](https://github.com/kleedaisuki/webdev-homework-2.0/pull/13)，W07 赛事列表和详情 [#15](https://github.com/kleedaisuki/webdev-homework-2.0/pull/15)，W06 团队展示 [#16](https://github.com/kleedaisuki/webdev-homework-2.0/pull/16)，W10 团队、成员及外链编辑 [#20](https://github.com/kleedaisuki/webdev-homework-2.0/pull/20)。 |
| 11 月 17 日 · 发布与交付 | W11 新建、编辑及发布演示赛果 [#22](https://github.com/kleedaisuki/webdev-homework-2.0/pull/22)，W13 团队 PDF 和个人 ZIP 下载入口 [#24](https://github.com/kleedaisuki/webdev-homework-2.0/pull/24)。 |
| 11 月 18 日 · D4 收口 | W08 留言表单 [#25](https://github.com/kleedaisuki/webdev-homework-2.0/pull/25) 与 W12 后台审核 [#28](https://github.com/kleedaisuki/webdev-homework-2.0/pull/28) 合成闭环；赛事数据扩至整届，新增小组对阵和淘汰赛晋级树 [#29](https://github.com/kleedaisuki/webdev-homework-2.0/pull/29)。 |
| 11 月 19–22 日 · D4 后交付 | W14 设计字典 [#39](https://github.com/kleedaisuki/webdev-homework-2.0/pull/39)；W17 双旅程和后台导航 [#40](https://github.com/kleedaisuki/webdev-homework-2.0/pull/40)，W09 首页验收 [#41](https://github.com/kleedaisuki/webdev-homework-2.0/pull/41)，W18 空库安装 SQL [#44](https://github.com/kleedaisuki/webdev-homework-2.0/pull/44)，均按各自 PR 追溯，不倒填进 D4。 |

**程序入口。** `web/index.php` 进入 Yii；`config/routes.php` 把公开 URL 映射到控制器（Controller）。控制器调用模型（Model）读写 MySQL，再交给视图（View）服务端渲染。Web 与命令行共用 `config/db.php`；`migrations/` 建表，`commands/` 管理种子、环境检查与管理员初始化；`assets/`、`web/` 提供本地样式和静态资源，`tests/` 留存旅程验收脚本。

<!-- page -->

## 02｜四条用户旅程：从 URL 追到状态与数据

### 赛事浏览 → 后台发布

- 前台：`/world-cup/matches` 与 `/world-cup/matches/{id}` → `WorldCupController` → `WorldCupPortal::matches()`、`Fixture` → `views/world-cup/`。列表按比赛状态筛选并分页；仅查本届已发布记录。小组/晋级树走 `/world-cup/groups`、`/world-cup/bracket` 和 JSON `/world-cup/draw-data`，由 `WorldCupDraw` 构建。
- 后台：`/admin/matches`、`/admin/matches/create`、`/admin/matches/{id}/edit` → `AdminFixtureController`、`FixtureForm` → `views/admin-fixture/`。创建演示草稿、独立发布、再录赛果。未开赛比分为 NULL，完赛须有两个非负整数，0:0 合法。正式记录只读。
- 数据：`fixture` 通过外键连接 `tournament`、两支 `national_team` 与 `venue`。库内和连接用 UTC，比赛时间界面用北京时间。发布和比赛状态分开，草稿详情返回 404。

### 团队展示 → 后台编辑

- 前台：`/team`、`/team/members/{id}` → `TeamController`、`ProjectTeam` / `ProjectMember` → `views/team/`。按发布状态读团队与成员。
- 后台：`/admin/team`、`/admin/members/{id}/edit` → `AdminTeamController` / `AdminMemberController` → `ProfileEditor` 验证并事务保存资料与外链。刷新原前台页即读到保存结果。
- 数据：`project_team`、`project_member`、`project_team_link`、`member_link`。

### 留言提交 → 审核后公开

- `GET /guestbook` 展示通过的内容；`POST /guestbook` 由 `GuestbookController` 写入 `GuestMessage`（待审核），视图在 `views/guestbook/`。
- `/admin/messages` 和 `POST /admin/messages/{id}/review` → `AdminGuestbookController` / `ReviewForm` → `GuestReview`。审核结果与留言状态在同一事务内保存；仅通过才公开。后台视图在 `views/admin-guestbook/`。
- 数据：`guest_message`、`guest_review`；审核人引用 `account`。各后台业务控制器复用管理员身份约束，登录由 `AuthController`、`Account` 处理。

### 作业下载

- `/downloads` → `DownloadController` / `views/download/index.php`；点击走 `/downloads/{category}/{filename}`。文件来自 `data/team/`、`data/personal/`，不是数据库里的附件记录。只列出类别允许且实际可读的文件，空栏有明确提示。

<!-- page -->

## 03｜四人贡献：12 条 MVC 路径

按仓库花名册对应姓名。表中记录**引入该文件的实际 Git 作者、提交和合并 PR**，不表示后续改动由同一人独占。每条路径均在 D4 基线存在，并已核对 PR 文件清单。

| 成员 · GitHub | 层 | 仓库路径 | 合并 PR / 引入提交 |
| --- | --- | --- | --- |
| 王子楸 · Clash727 | M | `models/GuestMessage.php` | [#25](https://github.com/kleedaisuki/webdev-homework-2.0/pull/25) · `0213f68` |
| 同上 | V | `views/guestbook/index.php` | #25 · `0213f68` |
| 同上 | C | `controllers/GuestbookController.php` | #25 · `0213f68` |
| 王艺霖 · zhixiu-feng | M | `models/ProjectMember.php` | [#16](https://github.com/kleedaisuki/webdev-homework-2.0/pull/16) · `ab0f5a2` |
| 同上 | V | `views/team/member.php` | #16 · `ab0f5a2` |
| 同上 | C | `controllers/TeamController.php` | #16 · `ab0f5a2` |
| 孙锘梵 · zustttt | M | `models/FixtureForm.php` | [#22](https://github.com/kleedaisuki/webdev-homework-2.0/pull/22) · `5b8bbb7` |
| 同上 | V | `views/admin-fixture/_form.php` | #22 · `5b8bbb7` |
| 同上 | C | `controllers/AdminFixtureController.php` | #22 · `5b8bbb7` |
| 郭嘉睿 · kleedaisuki | M | `models/GuestReview.php` | [#28](https://github.com/kleedaisuki/webdev-homework-2.0/pull/28) · `fa82b0a` |
| 同上 | V | `views/admin-guestbook/index.php` | #28 · `fa82b0a` |
| 同上 | C | `controllers/AdminGuestbookController.php` | #28 · `fa82b0a` |

`fa82b0a` 的 Git 作者显示名为 `moeSegFault`，GitHub 合并 PR 作者为 `kleedaisuki`。例如，W08 计划分给成员 4，实际公开提交来自 Clash727；W13 计划由成员 1 负责，实际下载 PR 是 kleedaisuki；两者不能用计划覆盖事实。赛事浏览控制器由 zustttt 在 #15 引入，后续 Clash727 在 #29 扩展，两个贡献分别计入。

**复核命令。** 在仓库根目录把 `<path>` 替换为任一表中路径；首个命令找该文件的引入者，再打开相应 PR 的 Files changed 检查其归属。

```sh
git log -1 --diff-filter=A --format='%h %an' bf36e97 -- <path>
git ls-tree bf36e97 -- <path>
```

<!-- page -->

## 04｜样本、运行边界与交接

| 样本集 | 来源与入库 | 在站点如何区分 |
| --- | --- | --- |
| W04 初始正式样本 | `data/seed/world-cup-sample.csv`：4 场来自 [FIFA 官方赛程及赛果](https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/articles/match-schedule-fixtures-results-teams-stadiums)，记录来源链接和核对时间。例如墨西哥 2:0 南非。 | `is_demo=0`，已发布；公开比赛详情有「FIFA 官方来源」。 |
| W04 课堂样本 | 同一 CSV 中 `demo-classroom`：演示甲队、演示乙队和演示球场。未开赛且无比分、无 FIFA 来源。 | `is_demo=1` 且 `publication_status=draft`。种子入库后只在后台可见；公开发布后的演示赛事明确标为「演示数据」。 |
| #29 整届扩展 | `data/seed/world-cup-2026.csv` 补齐另 100 场，并记录淘汰赛来源和胜负关联；`commands/SeedController.php` 合并两份 CSV。 | 共 104 场正式样本，加 1 场课堂草稿。数据为本地快照，不请求实时比分。 |

### 从实现证据走到课程验收

- **W20 展示。** 把上页的文件和 PR 对照用于讲解个人贡献。页面示例可用已归档的 [W17 双旅程验收](https://github.com/kleedaisuki/webdev-homework-2.0/blob/main/docs/w17-validation.md)，无需虚构工时或截图。
- **W22/W23 部署与复核。** [W18 #44](https://github.com/kleedaisuki/webdev-homework-2.0/pull/44) 合入的 `data/install.sql` 带 11 张业务表、独立的迁移历史和种子，不带账号与使用者留言。管理员在部署后本机创建。正式样本与课堂草稿的公开边界仍由应用查询控制。
- **W16/W24 个人提交。** 定稿时 [W16 #14](https://github.com/kleedaisuki/webdev-homework-2.0/issues/14) 仍开放，`data/personal/` 只有 `.gitkeep`。下载入口已实现，不等于 12 份个人作业已归档；以最终候选包实际文件为准。
