# 世界杯专题站

互联网数据库开发课程实验，使用 PHP、Yii2 Basic 和 MySQL，在本地运行。

## 实验环境

以下为 2026-11-13 的实测环境：

| 项目 | 版本 / 配置 |
| --- | --- |
| 操作系统 | Windows |
| PHP CLI | 8.2.12 |
| 框架 | Yii2 Basic，Yii 2.0.55 |
| 依赖管理 | Composer 2.11.2，版本由 `composer.lock` 锁定 |
| 数据库 | MySQL Community Server 8.4.9 |
| 字符集 / 排序规则 | `utf8mb4` / `utf8mb4_unicode_ci` |
| 时区 | PHP / Yii 使用 UTC，MySQL 会话使用 `+00:00` |
| Web 根目录 | `web/` |
| 本地访问地址 | `http://127.0.0.1:8080/` |

PHP 需启用 `ctype`、`mbstring`、`openssl`、`PDO` 和 `pdo_mysql` 扩展。

安装、启动与验收步骤见 [W01 工程初始化与验收](docs/w01-validation.md)。

首次部署请按 [数据库恢复说明](docs/install-sql.md) 把 `data/install.sql` 导入空 MySQL 库，再初始化管理员。也可选择迁移加种子的安装路径；两种方式只选一种。小组对阵与淘汰赛接口见 [赛事对阵说明](docs/fixture-draw.md)。
