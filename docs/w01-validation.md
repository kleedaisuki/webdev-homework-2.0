# W01 工程初始化与验收

日期：2026-11-13。基线：[W02 数据与路由契约](contracts.md)。

## 本次交付

- 从官方 Yii2 Basic 2.0.55 模板初始化并精简工程，提交 Composer 清单与锁文件。
- Web / CLI 共用 UTC、`utf8mb4` 配置，本机连接和随机 Cookie 密钥由忽略文件保存。
- 提供临时首页、统一错误页、本地 CSS / Yii / jQuery 资源和按 W02 预留的路由。
- 提供只读的 `php yii environment/check`，空库安装与本地启动步骤记录在本文。

模型目录、迁移目录和业务路由已预留；W05–W13 的业务功能尚未实现。W02 契约保持原样。

## 环境与配置

2026-11-13 在 Windows 上实测：

| 组件 | 版本 / 设置 |
| --- | --- |
| PHP CLI | 8.2.12，项目要求 PHP 8.2 或更高的 8.x |
| Composer | 2.11.2 |
| Yii | 2.0.55，精确依赖见根目录 `composer.lock` |
| MySQL Community Server | 8.4.9 |
| 数据库字符集 / 排序规则 | `utf8mb4` / `utf8mb4_unicode_ci` |
| PHP / Yii / MySQL 会话时区 | `UTC` / `UTC` / `+00:00` |
| Web 启动地址 | `http://127.0.0.1:8080/` |

原生安装 [PHP](https://www.php.net/downloads.php)、[Composer](https://getcomposer.org/download/) 和 [MySQL Community Server](https://dev.mysql.com/downloads/mysql/)，将各自命令目录加入 PATH。无需 Docker、Node.js 或公网部署。

PHP 启用 `ctype`、`mbstring`、`openssl`、`PDO`、`pdo_mysql`；Composer 下载 ZIP 包还需要 PHP `zip` 或系统解压工具。可用以下命令查看本机环境：

```text
php -v
php -m
composer --version
mysql --version
```

## 从空目录安装与启动

克隆仓库并进入项目根目录：

```text
git clone https://github.com/kleedaisuki/webdev-homework-2.0.git
cd webdev-homework-2.0
```

W01 合并前，检查分支时改用提供方的仓库 URL，并在 clone 后执行 `git switch member1/w01-project-init`。

安装锁文件中的依赖并生成本机配置：

```text
composer install --prefer-dist
php init.php
```

`php init.php` 创建两个被 Git 忽略的文件，重复执行保留现有内容：

- `config/db.local.php`：从 `config/db.local.example.php` 复制，填写本机连接。
- `config/web.local.php`：生成每台机器独立的随机 Cookie 密钥。

通过 `mysql -u root -p` 登录 MySQL 后建立空数据库：

```sql
CREATE DATABASE world_cup_dev
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

使用本机已有且具备该库建表、读写权限的开发账号，在 `config/db.local.php` 填写 DSN、用户名和密码。样例默认连接 `127.0.0.1:3306/world_cup_dev`；数据库名、端口与账号按本机实际情况修改。W01 本机验收使用独立 MySQL 实例的 `3307` 端口。

检查连接并启动：

```text
php yii environment/check
php yii serve 127.0.0.1 --port=8080
```

浏览器打开 **http://127.0.0.1:8080/**，应看到“世界杯专题站”和“站点准备中”。停止服务按 `Ctrl+C`。环境检查只读版本、字符集和时区；W01 无需执行迁移，也不提供演示账号。

Web 根目录为 `web/`。`runtime/` 与 `web/assets/` 分别保存本机日志、缓存和发布的资源，需要可写，生成内容不提交。依赖安装完成后，运行资源来自仓库或 Composer 安装的本地文件，不依赖 CDN。安装阶段如遇网络问题，配置本机代理或修复 CA 证书，保留 TLS 校验。

## 后续任务交接

- 路由预留在 `config/routes.php`，只有 `/` 已实现。后续页面归属、请求方法和数据结构沿用 [W02 契约](contracts.md)。
- W05 提供 `Account`、`LoginForm`、`AuthController`、`AdminBaseController` 及账号迁移；现有 `user` 配置已预留共享身份接口，W01 页面不实例化该组件。
- 模型放 `models/`，控制器放 `controllers/`，视图放 `views/`，迁移放 `migrations/`；团队作业、个人作业与样本分别放 `data/team/`、`data/personal/`、`data/seed/`。
- 命名、PHP 文件用途与作者注释头、UTC 存储和北京时间展示沿用 W02；PR 列出本人模型 / 视图 / 控制器路径，填写 `Closes #实际Issue号` 和实际验收结果。

## 已执行

| 检查 | 实际结果 |
| --- | --- |
| `composer validate --strict` | 通过 |
| `composer check-platform-reqs` | 通过 |
| `composer update --prefer-dist`（首次生成锁文件） | 安装 8 个依赖，Yii 2.0.55；Composer 未报告安全公告 |
| 项目 PHP 文件及 `yii` 的 `php -l` | 通过 |
| `php yii environment/check` | PHP 8.2.12、Yii 2.0.55、MySQL 8.4.9；必要扩展已启用 |
| 数据库字符集、排序规则与会话时区 | `utf8mb4`、`utf8mb4_unicode_ci`、`+00:00`；PHP / Yii 均为 UTC |
| `php yii serve 127.0.0.1 --port=8080` | Web 根目录为 `web/`，监听 `127.0.0.1:8080` |
| `GET /` 和浏览器显示 | HTTP 200；显示“世界杯专题站”“站点准备中”，无演示成员或赛事事实 |
| CSS、发布的 jQuery / Yii JS | 均为本地 URL，HTTP 200 |
| `POST /` | HTTP 405 |
| 未知路径、非法成员 ID、尚未实现的 `/team` | HTTP 404 |
| HTTP 访问配置文件、`composer.lock`、`data/` 文件 | HTTP 404 |
| `git check-ignore` | 本机配置、`.local/` 与 `vendor/` 已忽略 |

## 独立目录与空库复现

在独立目录 clone 本地 `member1/w01-project-init` 分支，未复制原工作目录的 `vendor/` 或本机配置。执行：

```text
composer install --prefer-dist --no-interaction
php init.php
php yii environment/check
php yii serve 127.0.0.1 --port=8081
```

`init.php` 后单独填写第二个空库 `world_cup_w01_check` 的连接。实测：

- 从锁文件安装全部 8 个依赖，未更新版本。
- 连接前确认目标库为 0 张表；环境检查未创建表，命令正常完成。
- MySQL 8.4.9、`utf8mb4`、`utf8mb4_unicode_ci`、UTC 配置均符合基线。
- 独立目录的 `GET /` 为 HTTP 200，页面没有外部资源 URL。
- 独立 clone 的 Git 工作区干净，本机配置未进入版本控制。

独立目录的 8081 测试服务已停止；主工作目录的 8080 服务保留供用户检查。

## 同伴验收

同机自测不能代替另一位组员独立操作。合并前请另一组员按本文“从空目录安装与启动”操作，填写其本机 MySQL 配置，运行 `environment/check` 后打开临时首页，在 PR 留下实际结果。

GitHub 项目看板、远端分支发布和 PR 在用户检查工程后处理；本次验证未修改远端仓库状态。

## 模板与许可

工程从官方 Yii2 Basic 2.0.55 模板精简而来，保留 Basic 目录结构及官方 `yii` 引导脚本，移除默认演示登录、联系表单、邮件和暂未使用的开发工具。

项目沿用仓库的 [GPL-3.0 许可](../LICENSE)，模板的版权与 BSD-3-Clause 声明保存在 [LICENSE-YII-BASIC.md](../LICENSE-YII-BASIC.md)，依赖包各自的许可随 Composer 安装保留。

参考：[Yii 安装指南](https://www.yiiframework.com/doc/guide/2.0/en/start-installation)、[Composer 基础用法](https://getcomposer.org/doc/01-basic-usage.md)。
