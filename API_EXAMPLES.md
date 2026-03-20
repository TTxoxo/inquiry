# API EXAMPLES

所有开放接口统一返回 JSON。

- 成功：`{"code":0,"message":"ok","data":{}}`
- 失败：`{"code":非0,"message":"错误说明","data":{},"errors":{}}`

## 1. GET /api/form/config 示例

### 请求

```bash
curl 'https://example.com/api/form/config?site_key=site_demo&form_key=contact_us&mode=inline'
```

### 成功响应示例

```json
{
  "code": 0,
  "message": "ok",
  "data": {
    "site_key": "site_demo",
    "form_key": "contact_us",
    "mode": "inline",
    "form": {
      "id": 1,
      "name": "Contact Us",
      "description": "Please leave your inquiry and we will get back to you soon."
    },
    "fields": [
      {
        "name": "name",
        "label": "Name",
        "type": "text",
        "is_required": 1,
        "sort": 10,
        "settings": {
          "placeholder": "Your name"
        }
      },
      {
        "name": "email",
        "label": "Email",
        "type": "email",
        "is_required": 1,
        "sort": 20,
        "settings": {
          "placeholder": "you@example.com"
        }
      },
      {
        "name": "message",
        "label": "Message",
        "type": "textarea",
        "is_required": 1,
        "sort": 30,
        "settings": {
          "placeholder": "How can we help?"
        }
      }
    ],
    "style_config": {
      "theme": "default",
      "layout": "inline",
      "submit_button_text": "Submit"
    },
    "tracking": {
      "type": "datalayer",
      "config": {},
      "channels": ["direct", "organic", "paid_search", "referral"]
    },
    "success_message": "Thank you, your inquiry has been submitted.",
    "pre_notice": {
      "enabled": false,
      "text": ""
    }
  }
}
```

## 2. POST /api/form/submit 示例

### 请求

```bash
curl 'https://example.com/api/form/submit' \
  -H 'Content-Type: application/json' \
  -d '{
    "site_key": "site_demo",
    "form_key": "contact_us",
    "fields": {
      "name": "Alice",
      "email": "alice@example.com",
      "phone": "+1-202-555-0188",
      "message": "Need product quotation."
    },
    "page_meta": {
      "page_url": "https://example.com/contact",
      "page_title": "Contact",
      "referrer_url": "https://google.com/"
    },
    "tracking_meta": {
      "utm_source": "google",
      "utm_medium": "cpc",
      "utm_campaign": "brand",
      "utm_term": "enterprise inquiry",
      "utm_content": "ad-a",
      "gclid": "test-gclid",
      "gbraid": "",
      "wbraid": ""
    },
    "honeypot": ""
  }'
```

### 成功响应示例

```json
{
  "code": 0,
  "message": "ok",
  "data": {
    "success_message": "Thank you, your inquiry has been submitted.",
    "data_layer_payload": {
      "event": "inquiry_submit",
      "form_id": 1,
      "site_id": 1
    },
    "inquiry_id": 1001,
    "inquiry_no": "INQ202603200001",
    "mail": {
      "sent": 1,
      "failed": 0,
      "logs": []
    }
  }
}
```

## 3. 失败响应示例

### 参数校验失败

```json
{
  "code": 422,
  "message": "Validation failed",
  "data": {},
  "errors": {
    "site_key": "site_key is required",
    "form_key": "form_key is required"
  }
}
```

### 业务失败示例

```json
{
  "code": 4003,
  "message": "Domain not allowed",
  "data": {},
  "errors": {}
}
```
