# CHECKLIST

## 上线前检查项

- [ ] 服务器 PHP 版本为 8.3。
- [ ] MySQL 版本为 8.0。
- [ ] Composer 版本为 2。
- [ ] Web 根目录已指向 `public/`。
- [ ] 伪静态已按 Nginx/Apache 示例配置完成。
- [ ] 发布包文件名符合 `enterprise-inquiry-system-YYYYMMDD-HHMMSS.zip`。
- [ ] 发布包已包含 `app/ config/ extend/ install/ public/ route/ vendor/ think composer.json composer.lock .env.example`。
- [ ] 发布包未包含 `.git/ .idea/ .vscode/ runtime/* tests/ node_modules/` 与本地临时文件。
- [ ] `vendor/autoload.php` 存在。
- [ ] `think` 存在并可执行。
- [ ] `.env` 已按服务器环境准备。
- [ ] `runtime/` 具备可写权限。
- [ ] cron 已加入 `php think mail:retry`。

## 安装后检查项

- [ ] `/install` 可访问。
- [ ] 安装流程中的环境检查通过。
- [ ] 数据库连接测试通过。
- [ ] 安装执行成功。
- [ ] 安装后 `/admin/login` 可访问。
- [ ] 管理员账号可登录后台。
- [ ] 后台首页 `/admin/dashboard` 可正常打开。
- [ ] 站点、表单、字段、SMTP、通知邮箱页面可正常进入。

## 表单提交流程检查项

- [ ] `GET /api/form/config` 返回 `{"code":0,"message":"ok","data":{...}}`。
- [ ] `POST /api/form/submit` 成功提交时返回 `{"code":0,"message":"ok","data":{...}}`。
- [ ] 缺少必填参数时返回失败 JSON，且包含 `errors` 字段。
- [ ] inline 模式嵌入表单可加载并提交。
- [ ] floating 模式嵌入表单可加载、展开并提交。
- [ ] 提交后后台询盘列表可见新数据。
- [ ] 域名限制按站点配置生效。
- [ ] 高频重复提交可触发限流或重复提交保护。

## 邮件检查项

- [ ] SMTP 配置已保存。
- [ ] SMTP 测试发送成功或返回可定位错误信息。
- [ ] 表单提交后通知邮件发送正常。
- [ ] 邮件失败时后台邮件日志可见失败记录。
- [ ] `php think mail:retry` 可执行。
- [ ] cron 触发后失败邮件可被重试。

## 导出检查项

- [ ] 后台询盘导出入口可访问。
- [ ] 导出文件可生成。
- [ ] 导出内容字段与后台展示一致。
- [ ] 导出文件编码与内容可正常打开。
