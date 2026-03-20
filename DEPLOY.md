# DEPLOY

## 1. 构建命令

在发布机或 CI 中执行以下命令，确保生产依赖完整并可完成基础启动检查：

```bash
composer install --no-dev --optimize-autoloader
php think
```

如果使用现成发布包且其中已包含 `vendor/`，仍建议在打包前执行一次以上命令，以确认交付内容可用。

## 2. 打包命令

正式发布包文件名格式必须为：

```text
enterprise-inquiry-system-YYYYMMDD-HHMMSS.zip
```

推荐打包命令：

```bash
zip -r enterprise-inquiry-system-$(date +%Y%m%d-%H%M%S).zip \
  app config extend install public route vendor think composer.json composer.lock .env.example \
  -x '.git/*' '.idea/*' '.vscode/*' 'runtime/*' 'tests/*' 'node_modules/*' '*.DS_Store' '*Thumbs.db' '*~'
```

## 3. 上传步骤

1. 在本地或 CI 产出 ZIP 发布包。
2. 上传到服务器临时目录，例如 `/data/package/`。
3. 创建新的发布目录，例如 `/data/www/enterprise-inquiry-system`。
4. 在发布目录中解压发布包。
5. 校验以下关键文件存在：
   - `vendor/autoload.php`
   - `think`
   - `public/index.php`
   - `composer.json`
6. 将站点根目录指向 `public/`。
7. 根据 Web 服务器类型启用 Nginx 或 Apache 配置。

## 4. 安装步骤

1. 复制环境模板：
   ```bash
   cp .env.example .env
   ```
2. 确保数据库已创建，字符集建议为 `utf8mb4`。
3. 浏览器访问 `http(s)://your-domain/install`。
4. 在安装页中执行环境检查。
5. 填写数据库连接信息并执行测试。
6. 提交安装，等待初始化完成。
7. 安装成功后访问 `http(s)://your-domain/admin/login`。
8. 使用安装阶段创建的管理员账号登录后台。

## 5. 伪静态说明

### Nginx

核心要求：

- 站点根目录必须指向项目的 `public/`。
- 不存在的文件与目录必须回退到 `/index.php`。

完整示例见 [nginx.example.conf](nginx.example.conf)。

### Apache

核心要求：

- 站点根目录必须指向项目的 `public/`。
- 需启用 `mod_rewrite`。
- 所有不存在的文件与目录必须重写到 `/index.php`。

完整示例见 [apache.example.conf](apache.example.conf)。

## 6. 权限说明

推荐运行用户为 Web 服务账号，例如 `www-data` 或 `nginx`。

至少应保证以下目录或文件对 Web 服务用户可读：

- `app/`
- `config/`
- `extend/`
- `install/`
- `public/`
- `route/`
- `vendor/`
- `think`
- `.env`

如运行过程中需要写入日志、缓存、会话，请确保 `runtime/` 可写：

```bash
mkdir -p runtime
chmod -R 775 runtime
chown -R www-data:www-data runtime
```

如果安装流程需要写入 `.env` 或安装锁文件，也应保证对应目录可写。

## 7. cron 说明

项目已提供 `mail:retry` 命令用于重试待发送邮件。建议配置系统 cron 每分钟执行一次：

```cron
* * * * * /usr/bin/php /path/to/project/think mail:retry >> /dev/null 2>&1
```

如需一次处理更多重试任务，可手动执行：

```bash
php think mail:retry 100
```

## 8. 冷启动检查说明

每次正式发布后，必须执行一次冷启动验证：

1. 在全新目录中解压发布包，不复用旧目录。
2. 检查 `vendor/autoload.php` 存在。
3. 检查 `think` 文件存在。
4. 配置虚拟主机后访问 `/install`，确认安装页可打开。
5. 完成安装后访问 `/admin/login`，确认后台登录页可打开。

## 9. 发布包内容说明

### 必须包含

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

### 不得包含

- `.git/`
- `.idea/`
- `.vscode/`
- `runtime/*`
- `tests/`
- `node_modules/`
- 本地临时文件
