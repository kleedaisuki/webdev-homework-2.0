# W11 验证记录：演示赛程的发布闭环

> 作者：孙锘梵（2410868，成员3）　分支：`member3/w11-admin-fixtures`

## 1. 本次交付

| 内容 | 路径 |
| --- | --- |
| M｜表单与发布规则 | `models/FixtureForm.php` |
| V｜新建/编辑共用的表单 | `views/admin-fixture/_form.php`（另含 `index.php`、`create.php`、`update.php`、`_notice.php`） |
| C｜后台赛事管理 | `controllers/AdminFixtureController.php` |
| 样式 | `web/css/admin-fixture.css`（独立文件，不改 `site.css` 与 `views/admin/`） |
| 回归脚本 | `tests/w11-fixtures.php` |
| 删除演示记录（范围外补充） | `AdminFixtureController::actionDelete()` + `views/admin-fixture/update.php` 的删除表单 |

状态机：`draft + scheduled`（游客不可见）→ 发布 → `published + scheduled`（前台显示「未开赛」）→ 录比分 → `published + finished`（`0:0` 合法）。

## 2. 环境与配置

| 项 | 实测值 |
| --- | --- |
| PHP | 8.2.35（NTS，`D:\php82`） |
| Yii | 2.0.55 |
| MySQL | 8.0.46 |
| 数据库 | `world_cup_dev`，`utf8mb4` / `utf8mb4_unicode_ci` |
| 会话时区 | `SET time_zone = '+00:00'` |
| 站点 | `php yii serve 127.0.0.1 --port=8080` |

## 3. 安装与启动

```bash
composer install --prefer-dist
php init.php
php yii migrate --interactive=0     # 含 m261115_000001_create_account_table
php yii seed/fixtures               # 4 条正式样本 + 1 条演示草稿
php yii account/create-admin        # 建管理员
php yii serve 127.0.0.1 --port=8080
```

## 4. 已执行的检查

| 检查 | 实际结果 |
| --- | --- |
| `php -l` 8 个新增 PHP 文件 | 全部通过 |
| `php yii migrate --interactive=0` | `m261115_000001_create_account_table` applied |
| `php tests/w11-fixtures.php` | **40 项全 PASS**，退出码 0 |
| 删除演示记录 | `POST /admin/matches/{id}/delete` → 303 到列表且行消失；`GET` 同路径 → 405；官方样本 → 403 |
| 后台入口未登录访问 | `GET /admin/matches` → 302 到 `/admin/login` |
| 正式样本写操作 | `GET /admin/matches/1/edit` → 403；`POST …/publish` → 403 |
| 不存在的比赛 | `/admin/matches/999999/edit` → 404 |
| 保存后的重定向 | 303 到 `/admin/matches/{id}/edit` |
| 时区换算 | 表单 `2026-06-20 20:30:00`（北京）→ 库内 `2026-06-20 12:30:00`（UTC） |

## 5. 回归脚本覆盖的完整闭环

`php tests/w11-fixtures.php http://127.0.0.1:8080`

1. 建临时管理员 → 登录 → 列表区分「演示/只读」。
2. 新建演示草稿：`is_demo=1`、`sample_key` 由服务端生成 `demo-*`、`publication_status=draft`、无比分；**表单里伪造 `is_demo=0` 与 `sample_key` 不生效**。
3. 草稿在前台列表不可见，详情 404。
4. 发布：`publication_status=published` 且写入 `published_at`；再发布一次，`published_at` 不变。
5. 录 `0:0`：确实写入 0 与 0，`score_updated_at` 刷新；前台 `?status=finished` 出现 `0 : 0`，`?status=scheduled` 不出现。
6. 只改小组不改比分：`score_updated_at` 保持不变。
7. 拒绝路径：已完赛缺比分、未开赛带比分、主队=客队，均返回 200 并显示错误、不落库。
8. 正式样本的编辑与发布均 403；未知 id 404。
9. 删除演示记录：`POST …/delete` 后行消失、前台详情 404；`GET …/delete` → 405；官方样本 → 403（官方只读这条不因新增删除而放松）。

## 6. 与契约和线框的对照

| 约束 | 落点 |
| --- | --- |
| 后台强制 `is_demo=1`、`sample_key=demo-*` | `FixtureForm::save()` 新建分支 |
| 官方样本只读、服务端拒绝 | `AdminFixtureController::findDemoFixture()` 抛 403 |
| 保存与发布是两个独立 CSRF POST | `_form.php` 与 `update.php` 各自成表单 |
| 改回未开赛清空比分与 `score_updated_at` | `save()` 的 scheduled 分支 |
| 只有比分或状态变化才刷新 `score_updated_at` | `save()` 比较前后值 |
| 未开赛不显示比分 | 复用 W07 的 `Fixture::displayScore()` 返回 `null` |
| 线框字段与文案（开球含秒、比分留空规则） | `views/admin-fixture/_form.php` |

## 7. 仍待确认

- **同伴验收**：请另一位组员在独立目录与空库按第 3、5 节复现一遍，并在 PR 里回贴结果。
- 正式样本行在列表里的「只读」是纯文本还是链接样式，等 W10 合并后统一。

## 8. 边界与交接

- 不含：实时比分、积分榜、淘汰赛阶段（`stage` 固定 `group`）、团队资料与留言审核（W10/W12）。
- 后台删除演示记录不在 W11 原始范围内，是按使用者反馈补的；官方样本仍严格只读（服务端 403）。
- 状态图请 W14 引用 `draft/scheduled → published/scheduled → published/finished` 三条边。
- W15 用户手册的后台操作顺序：登录 → 赛事管理 → 新建演示比赛 → 保存 → 发布 → 前台按状态筛选查看。
- W21 演示脚本可复用同一顺序，录比分时展示 `0:0` 与「未开赛」的差别。
