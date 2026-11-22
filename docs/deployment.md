# 世界杯专题站 · 部署文档

**星河工坊｜互联网数据库开发课程设计｜W22｜2026-11-22**

2412712 王子楸 · 2410824 王艺霖 · 2410868 孙锘梵 · 2413174 郭嘉睿

## 01｜准备环境、源码与依赖

本手册供助教和 W23 独立验收使用。**默认路线：在新空库导入 `data/install.sql` → 创建本机管理员 → 启动 Yii → 浏览器验收。** 只在本机运行，不需要 Docker、Node.js、Apache 或公网服务。

| 组件 | W22 空库恢复实测（Linux） | W01 原生环境记录（Windows） |
| --- | --- | --- |
| PHP CLI | 8.4.28 | 8.2.12 |
| Yii2 Basic / Yii | 2.0.55 | 2.0.55 |
| Composer | 2.11.2 | 2.11.2 |
| MySQL Community Server | 8.4.13 | 8.4.9 |

W22 基于 [`cf8bf60`](https://github.com/kleedaisuki/webdev-homework-2.0/commit/cf8bf60) 及已合并的 [W18 SQL](https://github.com/kleedaisuki/webdev-homework-2.0/pull/44)。Windows 列是工程基线记录，不代表本轮重跑了 Windows 全流程。

1. 原生安装 [PHP 8.2 或更新的 8.x](https://www.php.net/downloads.php)、[Composer 2](https://getcomposer.org/download/) 与 [MySQL Community Server 8.4](https://dev.mysql.com/downloads/mysql/)，将 `php`、`composer`、`mysql` 所在目录加到 PATH，并启动安装器配置的本机 MySQL 实例。
2. 用 `php --ini` 找 CLI 实际加载的配置；`php -m` 应列出 `ctype`、`mbstring`、`openssl`、`PDO`、`pdo_mysql`。Windows 按需在 `php.ini` 启用 `extension=mbstring`、`extension=openssl`、`extension=pdo_mysql`，确认 `extension_dir` 指到 PHP 的 `ext` 目录；Linux 可通过发行版包安装对应 PHP 版本的 MySQL 与 mbstring 扩展。Composer 解包需启用 `zip` 或安装解压工具。
3. 使用课程给定的源码目录并进入根目录（可见 `yii`、`composer.lock`、`data/install.sql`）；没有源码包时，可克隆仓库再切到课程给定的提交。

```sh
git clone https://github.com/kleedaisuki/webdev-homework-2.0.git
cd webdev-homework-2.0
```

```sh
composer install --prefer-dist
php init.php
```

按 [Composer 锁文件安装](https://getcomposer.org/doc/01-basic-usage.md#installing-from-composer-lock)，不要用 `composer update`。首次取依赖可能需要网络；启动后页面使用本地资源。

<!-- page -->

## 02｜创建空库、填写连接、导入 SQL

以本机 MySQL 管理账号登录（例如 `mysql -u root -p`），在 `mysql>` 执行以下 SQL。把示例密码替换为自选的本机数据库密码；若库名或用户名已被占用，换一组新名称并在后续步骤保持一致，不覆盖旧库。

```sql
CREATE DATABASE world_cup_dev
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'world_cup_dev'@'127.0.0.1'
  IDENTIFIED BY 'REPLACE_WITH_LOCAL_DB_PASSWORD';
GRANT ALL PRIVILEGES ON world_cup_dev.*
  TO 'world_cup_dev'@'127.0.0.1';
```

编辑 `php init.php` 生成的 `config/db.local.php`。`dbname`、端口和账号应与导入命令一致。下面用 MySQL 默认端口 3306；W22 实测用独立实例的 13307，命令的 `-P` 和 DSN 的 `port` 同时调整。保留自动生成的 `config/web.local.php`，不复制别人的 Cookie 密钥，不提交这两个本机文件。

```php
<?php
return [
    'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=world_cup_dev',
    'username' => 'world_cup_dev',
    'password' => 'REPLACE_WITH_LOCAL_DB_PASSWORD',
];
```

### 导入（两种终端写法选一种）

**Bash / POSIX**：在仓库根目录执行。反斜线是 Bash 续行符，密码由 MySQL 客户端提示输入。

```sh
mysql -h 127.0.0.1 -P 3306 -u world_cup_dev -p \
  --default-character-set=utf8mb4 world_cup_dev < data/install.sql
```

**Windows PowerShell**：同样从仓库根目录运行，先连客户端，再在 `mysql>` 运行 `SOURCE`（不是在 PowerShell 里运行）。

```text
mysql -h 127.0.0.1 -P 3306 -u world_cup_dev -p --default-character-set=utf8mb4 world_cup_dev
mysql> SOURCE data/install.sql
mysql> EXIT
```

SQL 已包含 11 张业务表、Yii 迁移历史、赛事及团队种子。导入后**不再运行迁移或播种**；导入失败先修正错误，再选一个新的空库重试。参见 [MySQL 官方恢复说明](https://dev.mysql.com/doc/refman/8.4/en/reloading-sql-format-dumps.html)。

<!-- page -->

## 03｜检查连接、创建管理员、启动

在仓库根目录运行：

```sh
php yii environment/check
php yii account/create-admin
php yii serve 127.0.0.1 --port=8080
```

- **检查连接**：应显示 Yii 2.0.55、`pdo_mysql: enabled`、目标数据库、`utf8mb4` / `utf8mb4_unicode_ci`，PHP / Yii 时区为 UTC，MySQL 会话时区为 `+00:00`。
- **创建管理员**：SQL 不带账号。命令逐项提示用户名（3–64 位小写字母、数字或下划线）、展示名、密码（8–72 字节）和确认密码。密码在终端交互输入；用刚建的账号登录，不依赖旧演示账号。
- **启动**：终端保持运行，打开 **http://127.0.0.1:8080/**。Web 根目录为 `web/`；`runtime/` 和 `web/assets/` 要可写。演示结束后按 Ctrl+C 停止。若端口已用，可调到 8081 并同步更换浏览器地址。

### 常见问题：先定位哪一层没通

| 现象 | 检查与处理 |
| --- | --- |
| `could not find driver` 或找不到扩展 | 对照 `php --ini` 与 `php -m`，给当前 CLI 版本启用 `pdo_mysql`。 |
| `Connection refused` 或 `Access denied` | 先用与 DSN 相同的地址、端口、账号通过 `mysql` 命令连接；检查服务是否启动、账号的 Host 和数据库权限。 |
| `Unknown database` 或找不到表 | 核对库名、端口及导入输出。不要把 SQL 再导到同一半成品库，改用新的空库。 |
| 首页无样式或资源错误 | 检查依赖完整性、`web/assets/` 可写性及终端/ `runtime/logs/` 日志。 |
| 登录后报 `session_start()` 写入错误 | 检查 `php.ini` 的 `session.save_path` 是否存在且当前用户可写；如用自解压 PHP，可改为自己创建的本机会话目录，再重启 Yii。 |

### 替代路线：仅限未导入 SQL 的空库

仅在需要从源码重建时选用，把第 02 页的 SQL 导入替换为下列三条命令（先创建空库和填写连接，再按顺序执行）。随后回到本页创建管理员并启动。默认恢复验收使用的是 SQL，不是这条路线。

```sh
php yii migrate --interactive=0
php yii seed/fixtures
php yii team-seed
```

<!-- page -->

## 04｜助教验收与 W23 交接

**W22 实测：** Linux 原生 PHP CLI 与 MySQL，在独立的新库中检查导入前 0 张表；执行默认 SQL 导入后有 11 张业务表和 1 张迁移历史表、104 场正式比赛、1 场草稿、4 位成员、0 个账号。然后用交互命令创建管理员并运行 `php yii serve 127.0.0.1 --port=8080`。以下 URL 均相对该地址。

| 顺序 | 浏览器操作 | 实测与应看到的结果 |
| --- | --- | --- |
| 1 · 正式赛事 | 开 `/`、`/world-cup/matches`，再开 `/world-cup/matches/1`。 | 首页和列表可用；详情是墨西哥 2:0 南非，有「FIFA 官方来源」链接。检验站点不用点开外站。 |
| 2 · 草稿边界 | 用访客会话开 `/world-cup/matches/5`；另一个会话在 `/admin/login` 用新账号登录，点「赛事管理」；种子库草稿在 `/admin/matches?page=6`。 | 访客详情 HTTP 404，公开列表不出现演示甲队；后台可见种子草稿及编辑入口。 |
| 3 · 团队关联 | 打开 `/team` 并点一个成员。 | 团队有 4 张成员卡片，可到成员详情；初始种子姓名为占位名。 |
| 4 · 团队下载 | 首页点「作业下载」，打开团队栏中的一份 PDF。 | 浏览器下载的文件名及字节与仓库里的 PDF 一致。 |
| 5 · 个人下载 | 同页查看「个人作业」，有正式归档时下载一份 ZIP 并解压。 | 当前 [W16 #14](https://github.com/kleedaisuki/webdev-homework-2.0/issues/14) 未交付，干净候选包显示「暂无可下载的个人作业」。W22 在隔离副本加一个临时验证 ZIP，下载并通过完整性核验后删除；这不替代真实归档。 |

浏览器验收拦截了所有站外请求，上述页面和下载链路仍正常，且没有发起站外资源请求。FIFA 来源与团队外链是可选导航，不是运行依赖。

**给成员 2（W23）：** 只用本手册，在另一台机器或独立目录加新库执行默认 SQL 路线，把源码 SHA、软件版本、命令、导入结果、各 URL 状态和实际打开的下载文件名留在验收记录。若候选包已有 W16 正式 ZIP，用真实文件验收并核对 4×3 清单；当前完成的是 W22 作者自验，成员 2 的独立结果由 W23 补录。
