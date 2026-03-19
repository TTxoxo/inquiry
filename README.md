# Inquiry

## 项目名称
Inquiry 企业多站点询盘表单管理系统

## 项目目标
本项目是一个基于 ThinkPHP 8 的正式生产项目，用于构建企业多站点询盘表单管理系统。当前仓库处于分阶段建设中，现阶段仅建立仓库指导层与基础协作约束，后续阶段再逐步补充基础启动层、安装流程、后台、开放 API 与前端嵌入能力。

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
- 时区 Asia/Shanghai
- 后台 zh-CN
- 前端 en

## 目录结构
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
├─ public/assets/{admin,embed,install}/
├─ public/index.php
├─ route/
├─ runtime/
├─ vendor/
├─ think
├─ composer.json
├─ composer.lock
└─ .env.example
```

## 当前状态
- 当前项目处于分阶段建设中。
- 当前阶段仅维护仓库指导文件，不包含业务代码实现。
- 完整部署文档将在后续阶段补齐。

## 基础运行命令
```bash
composer install --no-dev --optimize-autoloader
php think
```
