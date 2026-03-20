# EMBED EXAMPLES

嵌入脚本与样式文件位于：

- `/assets/embed/embed.js`
- `/assets/embed/embed.css`

脚本会自动请求：

- `GET /api/form/config`
- `POST /api/form/submit`

## 1. inline 模式嵌入示例

```html
<div class="inquiry-embed"
     data-site-key="site_demo"
     data-form-key="contact_us"
     data-mode="inline"></div>
<script src="https://example.com/assets/embed/embed.js?site_key=site_demo&form_key=contact_us&mode=inline"></script>
```

也可以使用手动初始化方式：

```html
<div id="inquiry-inline"></div>
<script src="https://example.com/assets/embed/embed.js"></script>
<script>
  window.InquiryEmbed.init({
    siteKey: 'site_demo',
    formKey: 'contact_us',
    mode: 'inline',
    container: '#inquiry-inline',
    apiBase: 'https://example.com'
  });
</script>
```

## 2. floating 模式嵌入示例

```html
<script src="https://example.com/assets/embed/embed.js?site_key=site_demo&form_key=contact_us&mode=floating"></script>
```

也可以使用手动初始化方式：

```html
<script src="https://example.com/assets/embed/embed.js"></script>
<script>
  window.InquiryEmbed.init({
    siteKey: 'site_demo',
    formKey: 'contact_us',
    mode: 'floating',
    apiBase: 'https://example.com'
  });
</script>
```

## 3. 使用说明

- `site_key` 与 `form_key` 必须与系统中已配置的站点与表单对应。
- `mode` 仅支持 `inline` 或 `floating`。
- 嵌入域名必须与站点允许域名一致，否则接口会返回 `Domain not allowed`。
- 提交成功后，若页面已有 `window.dataLayer` 数组，脚本会尝试推送追踪事件。
