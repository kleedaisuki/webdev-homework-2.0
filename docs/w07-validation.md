# W07 验收记录：世界杯赛程与赛果浏览

本条对应 Issue「[W07] 交付世界杯赛程与赛果浏览」，负责人成员3（孙锘梵，2410868），前置依赖 W01 工程骨架、W02 数据契约、W04 视觉规范与样本数据。

## 1. 本次交付

| 类型 | 文件 | 说明 |
| --- | --- | --- |
| 迁移 | `migrations/m261115_100001_create_tournament_table.php` | 届次表，`edition` 唯一 |
| 迁移 | `migrations/m261115_100002_create_national_team_table.php` | 国家队表，三位代码唯一 |
| 迁移 | `migrations/m261115_100003_create_venue_table.php` | 场馆表，`(name, city)` 唯一 |
| 迁移 | `migrations/m261115_100004_create_fixture_table.php` | 比赛表，4 个 RESTRICT 外键 + 8 个 CHECK |
| 模型 | `models/Tournament.php` | 届次 |
| 模型 | `models/NationalTeam.php` | 国家队 |
| 模型 | `models/Venue.php` | 场馆 |
| 模型 | `models/Fixture.php` | 比赛；`findPublished()`、`displayScore()`、时区换算、状态与比分校验 |
| 命令 | `commands/SeedController.php` | `php yii seed/fixtures`，整文件校验 + 事务导入 + 幂等 |
| 控制器 | `controllers/WorldCupController.php` | `/world-cup/matches` 列表（筛选、分页）与 `/world-cup/matches/{id}` 详情 |
| 视图 | `views/world-cup/index.php` | 列表页，含空态与分页 |
| 视图 | `views/world-cup/view.php` | 详情页，含来源与演示标识 |
| 样式 | `web/css/world-cup.css` | 按 `docs/ui.md` 的 Pitch notebook 配色与排版 |
| 验证脚本 | `tests/w07-matches.php` | `php tests/w07-matches.php`，对运行中的站点做 17 项 HTTP 检查 |

M/V/C 归属：**M** `models/Fixture.php`、**V** `views/world-cup/index.php`、**C** `controllers/WorldCupController.php`。

## 2. 环境与配置

| 项 | 实测值 |
| --- | --- |
| 操作系统 | Windows 11 |
| PHP | 8.2.35（NTS x64，`D:\php82`） |
| Composer | 2.11.2 |
| Yii | 2.0.55（yii2-app-basic） |
| MySQL | 8.0.46，服务端 `utf8mb4_0900_ai_ci` |
| 数据库 | `world_cup_dev`，`utf8mb4` / `utf8mb4_unicode_ci` |
| 连接账号 | 本机专用账号，仅授权 `world_cup_dev.*`，凭证写在未入库的 `config/db.local.php` |
| 会话时区 | `config/db.php` 在 `afterOpen` 执行 `SET time_zone = '+00:00'`，与 PHP、Yii 的 UTC 一致 |
| 访问地址 | `http://127.0.0.1:8080/` |

服务器 `@@time_zone` 仍是 `SYSTEM`（本机 +08:00），因此**不能依赖数据库默认值写时间**，所有 `created_at` / `updated_at` 由模型的 `TimestampBehavior` 以 `gmdate()` 写入 UTC。

## 3. 从空库安装与启动

```bash
composer install --prefer-dist
php init.php                      # 生成 config/db.local.php 与 config/web.local.php
# 在 config/db.local.php 填本机账号
CREATE DATABASE world_cup_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php yii migrate --interactive=0   # 4 个迁移
php yii seed/fixtures             # 导入 data/seed/world-cup-sample.csv
php yii serve 127.0.0.1 --port=8080
```

## 4. 已执行检查

| 检查 | 命令 / 请求 | 实际结果 |
| --- | --- | --- |
| 语法 | `php -l` × 12 个新文件 | 全部 `No syntax errors detected` |
| 迁移 | `php yii migrate --interactive=0` | 4 个迁移全部 applied，建 4 表 + 8 个 CHECK |
| 导入 | `php yii seed/fixtures` | 比赛 新建 5 / 更新 0 / 保留演示 0；国家队 10，场馆 5，届次 1 |
| 幂等 | 再次 `php yii seed/fixtures` | 比赛 新建 0 / 更新 4 / 保留演示 1；国家队 0，场馆 0，届次 0（无重复行） |
| 列表页 | `GET /world-cup/matches` | 200，7232 字节，4 场官方样本，「官方来源」徽标 4 次，演示记录未出现 |
| 筛选已完赛 | `GET /world-cup/matches?status=finished` | 200，4 场，全部含比分 |
| 筛选未开赛 | `GET /world-cup/matches?status=scheduled` | 200，显示空态「暂无符合条件的比赛」，无官方场次 |
| 非法筛选 | `GET /world-cup/matches?status=abc` | 400 |
| 详情 | `GET /world-cup/matches/1` | 200，显示「2 : 0」、FIFA 官方来源、`2026-06-12 03:00 北京时间 / UTC+08:00` |
| 不存在 | `GET /world-cup/matches/9999` | 404 |
| 非数字 | `GET /world-cup/matches/abc` | 404（路由正则不匹配） |
| 草稿不可见 | `GET /world-cup/matches/5`（`demo-classroom`） | 404 |
| 退出码 | `php yii seed/fixtures` | 0 |
| 回归脚本 | `php tests/w07-matches.php` | 17 项检查全部 PASS，失败 0 项，退出码 0（含草稿详情 404 一项，脚本自行从 `config/db.local.php` 查 `demo-classroom` 的 id） |

时区换算核对：`official-2026-001` 本地 `2026-06-11T13:00:00-06:00` → UTC `19:00` → 北京 `2026-06-12 03:00`，页面显示一致；`official-2026-003` 本地 `15:00-04:00` → 北京 `2026-06-13 03:00`。

## 5. 独立空库复现

在另一个空库 `world_cup_w07_check`（临时授权、检查后已删除）中从零执行：

| 步骤 | 结果 |
| --- | --- |
| `php yii migrate --interactive=0` | 4 个迁移 applied |
| `php yii seed/fixtures`（第一次） | 新建 5 / 更新 0 / 保留演示 0 |
| `php yii seed/fixtures`（第二次） | 新建 0 / 更新 4 / 保留演示 1 |
| 行数 | `tournament` 1、`national_team` 10、`venue` 5、`fixture` 5，其中 published 4、demo-classroom 仍为 draft |
| CHECK 数量 | 8 |

## 6. 数据库层约束验证

直接向 `fixture` 插入非法数据，均由 CHECK 拒绝（`ERROR 3819`）：

| 非法数据 | 结果 |
| --- | --- |
| `finished` 但比分为 NULL | 拒绝 |
| `scheduled` 却填了 `1:0` | 拒绝 |
| 主队与客队相同 | 拒绝 |
| `draft` 却有 `published_at` | 拒绝 |
| `is_demo=0` 但缺来源 | 拒绝 |
| `finished` 且 `0:0` | 接受，说明 0 不会被误判为空 |

## 7. 页面对照实验：空比分与 `0:0` 是两个状态

样本 CSV 里没有「已发布但未开赛」和「`0:0` 完赛」的记录，所以这两条状态单独做一次对照实验：临时插入两条 `is_demo=1`、`publication_status='published'` 的记录，看页面渲染，验完立即删除，`fixture` 表回到 5 行，不写入 `data/seed/`。

| 临时记录 | 关键字段 | 列表页 | 详情页 |
| --- | --- | --- | --- |
| `tmp-w07-scheduled` | `scheduled`、比分 NULL | 显示「未开赛」，不渲染比分 | 比分板显示「未开赛」，全页不含 `0 : 0` |
| `tmp-w07-goalless` | `finished`、`0:0` | 显示「0 : 0」 | 显示「0 : 0」，带「演示数据」徽标 |

同时验证：`?status=scheduled` 筛选下只出现 `tmp-w07-scheduled` 一场，`?status=finished` 下只出现 `tmp-w07-goalless` 这一条临时记录与 4 场官方样本。

结论：空比分渲染为「未开赛」、真实 `0:0` 渲染为「0 : 0」，两者在视图层就是两条不同分支（`Fixture::displayScore()` 返回 `null` 还是 `"0 : 0"`），没有用 `empty()`、`?: 0` 之类的写法把它们混在一起。

## 8. 同伴验收（待完成）

请另一位组员在自己的机器上，用独立数据库执行第 3 节的命令，然后：

1. 打开 `/world-cup/matches`，确认只有 4 场官方样本，且 `demo-classroom` 不出现在任何筛选下；
2. 打开任一场详情，核对北京时间与来源链接；
3. 把复现结果（成功/失败、截图）贴到本条 PR 的评论里。

## 9. 已知边界与交接

- 前台为只读，发布与改比分留给 W11（后台发赛果），本条不提供任何写入入口。
- 首页摘要模块（取最近 3 场已完赛）属于 W09，本条只提供 `Fixture::findPublished()`，它已带 `publication_status` 过滤，W09 在其上追加 `match_status = finished` 与 `score_updated_at DESC` 即可。
- 分页每页 20 条，当前样本只有 4 场，`?page=2` 会显示空态；翻页保留 `status` 参数。
- 演示记录以 `sample_key` 识别，重复导入不会覆盖已被编辑的演示数据。
- 名单、截止时间等非本条范围的需求（如淘汰赛、积分榜）不在实现内，与 `docs/requirements.md` 的范围声明一致。
