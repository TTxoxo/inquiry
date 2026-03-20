# Enterprise Inquiry System

企业多站点询盘表单管理系统（Enterprise Inquiry System）是一个基于 ThinkPHP 8.1 的正式生产项目，用于为企业站点提供安装初始化、后台管理、嵌入式表单、开放 API、邮件通知、线索导出与运维交付能力。

## 功能范围

- 安装向导：`/install` 提供环境检查、数据库测试与初始化安装。
- 后台管理：`/admin/login` 登录后可管理站点、站点用户、表单、字段、嵌入配置、询盘、SMTP、通知邮箱、追踪配置、垃圾词、操作日志与登录日志。
- 开放 API：提供 `GET /api/form/config` 与 `POST /api/form/submit` 两个公开接口，统一返回 JSON。
- 嵌入表单：通过 `public/assets/embed/embed.js` 与 `public/assets/embed/embed.css` 以 inline / floating 两种模式嵌入外部站点。
- 邮件与重试：支持 SMTP 发信与 `mail:retry` 定时重试。
- 线索导出：后台支持询盘导出。

## 技术栈

- PHP 8.3
- ThinkPHP 8.1
- Think ORM 4
- Think Filesystem 3
- MySQL 8.0
- Composer 2
- Layui 2.13.x
- 原生 HTML / CSS / JavaScript
- embed.js + embed.css
- cache / session / log 默认 file
- 时区 `Asia/Shanghai`
- 后台语言 `zh-CN`
- 前端语言 `en`

## 目录结构说明

```text
project/
├─ app/
│  ├─ admin/{controller,service,validate,view}
│  ├─ api/{controller,service,validate}
│  ├─ install/{controller,service,validate,view}
│  ├─ common/{controller,middleware,traits,helper.php}
│  ├─ command/
│  ├─ model/
│  └─ service/
├─ config/
├─ extend/geoip/
├─ install/sql/
├─ public/
│  ├─ assets/{admin,embed,install}/
│  └─ index.php
├─ route/
├─ runtime/
├─ vendor/
├─ think
├─ composer.json
├─ composer.lock
└─ .env.example
```

## 安装步骤

1. 准备 PHP 8.3、MySQL 8.0、Composer 2 与 Web 服务器。
2. 将发布包完整上传到目标目录，确保包含 `vendor/`。
3. 复制环境文件：
   ```bash
   cp .env.example .env
   ```
4. 如需重新生成生产依赖，可执行：
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
5. 配置站点根目录到 `public/`。
6. 配置伪静态，保证所有请求回退到 `public/index.php`。
7. 浏览器访问 `/install`，按安装向导完成环境检查、数据库连接测试与初始化。
8. 安装完成后访问 `/admin/login`，使用安装阶段创建的后台账号登录。

更完整的上线流程请见 [DEPLOY.md](DEPLOY.md)。

## 部署步骤

### 1. 生产构建

```bash
composer install --no-dev --optimize-autoloader
php think
```

### 2. 打包发布

推荐打包文件名格式：

```text
enterprise-inquiry-system-YYYYMMDD-HHMMSS.zip
```

示例：

```bash
zip -r enterprise-inquiry-system-$(date +%Y%m%d-%H%M%S).zip \
  app config extend install public route vendor think composer.json composer.lock .env.example \
  -x '.git/*' '.idea/*' '.vscode/*' 'runtime/*' 'tests/*' 'node_modules/*' '*.DS_Store' '*Thumbs.db'
```

### 3. 上传与安装

- 上传 ZIP 包到服务器。
- 解压到正式目录。
- 校验 `vendor/autoload.php` 与 `think` 文件存在。
- 配置 Nginx 或 Apache 站点根目录到 `public/`。
- 访问 `/install` 完成安装。
- 安装成功后检查 `/admin/login` 可访问。

Nginx 与 Apache 示例配置分别见 [nginx.example.conf](nginx.example.conf) 与 [apache.example.conf](apache.example.conf)。

## 常用命令

```bash
composer install --no-dev --optimize-autoloader
php think
php think mail:retry
php think mail:retry 100
```

## cron 说明

使用系统计划任务每分钟执行一次邮件重试命令：

```cron
* * * * * /usr/bin/php /path/to/project/think mail:retry >> /dev/null 2>&1
```

如需单次提高处理上限，可手动执行：

```bash
php think mail:retry 100
```

## 发布包说明

### 发布包文件名

- `enterprise-inquiry-system-YYYYMMDD-HHMMSS.zip`

### 发布包必须包含

- `app/`
- `config/`
- `extend/`
- `install/`
- `public/`
- `route/`
- `vendor/`
- `think`
- `composer.json`
- `composer.lock`
- `.env.example`

### 发布包不得包含

- `.git/`
- `.idea/`
- `.vscode/`
- `runtime/*`
- `tests/`
- `node_modules/`
- 本地临时文件（如 `.DS_Store`、`Thumbs.db`、编辑器 swap 文件等）

## 冷启动检查

正式发布后的冷启动验证应至少覆盖以下内容：

1. 在全新目录中解压发布包。
2. 检查 `vendor/autoload.php` 是否存在。
3. 检查 `think` 是否存在且可执行。
4. 检查 `/install` 是否可访问。
5. 完成安装后检查 `/admin/login` 是否可访问。

完整检查项见 [CHECKLIST.md](CHECKLIST.md)。

## 与 AGENTS.md 的分工说明

- `README.md`、`DEPLOY.md`、`CHECKLIST.md`、`API_EXAMPLES.md`、`EMBED_EXAMPLES.md` 面向项目交付、部署、验收与运维使用。
- `AGENTS.md` 是 Codex 在仓库内工作的指导文件，约束阶段边界、目录规范、实现范围与交付要求，不作为生产部署手册替代品。
