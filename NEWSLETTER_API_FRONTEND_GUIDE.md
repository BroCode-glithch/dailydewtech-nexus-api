# Newsletter API Frontend Integration Guide

This guide explains exactly how your frontend should consume the new Newsletter API.

## Base URL

```
http://127.0.0.1:8000/api
```

---

## 1) Public Endpoints (No Auth)

## Subscribe

**POST** `/newsletter/subscribe`

### Request Body

```json
{
    "email": "user@example.com",
    "name": "John Doe",
    "source": "website-footer"
}
```

### Success Response (201 on first subscribe, 200 if already existed)

```json
{
    "success": true,
    "message": "Subscribed successfully.",
    "data": {
        "email": "user@example.com",
        "status": "active"
    }
}
```

### Validation Error (422)

```json
{
    "success": false,
    "errors": {
        "email": ["The email field is required."]
    }
}
```

---

## Unsubscribe

**POST** `/newsletter/unsubscribe`

### Request Body

```json
{
    "email": "user@example.com",
    "token": "UNSUBSCRIBE_TOKEN_FROM_EMAIL"
}
```

### Success Response

```json
{
    "success": true,
    "message": "You have been unsubscribed successfully."
}
```

### Invalid Token/Email (404)

```json
{
    "success": false,
    "message": "Invalid unsubscribe request."
}
```

---

## 2) Admin Endpoints (Sanctum Auth Required)

> Send bearer token in headers:

```
Authorization: Bearer <TOKEN>
```

## List Subscribers (Paginated + Filterable)

**GET** `/admin/newsletter/subscribers`

### Query Params

- `page` (optional): default `1`
- `per_page` (optional): default `20`
- `status` (optional): `active` | `unsubscribed`
- `search` (optional): matches email or name
- `sort` (optional): e.g. `created_at`, `email`, `name`, `status`
- `direction` (optional): `asc` | `desc` (default `desc`)

### Example

`/admin/newsletter/subscribers?status=active&search=gmail&page=1&per_page=25`

### Response

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "email": "user@example.com",
                "name": "John Doe",
                "status": "active",
                "unsubscribe_token": "...",
                "subscribed_at": "2026-02-18T14:20:00.000000Z",
                "unsubscribed_at": null,
                "source": "website-footer",
                "meta": null,
                "created_at": "2026-02-18T14:20:00.000000Z",
                "updated_at": "2026-02-18T14:20:00.000000Z"
            }
        ],
        "last_page": 1,
        "per_page": 20,
        "total": 1
    }
}
```

---

## Update Subscriber Status

**PATCH** `/admin/newsletter/subscribers/{id}`

### Request Body

```json
{
    "status": "unsubscribed",
    "name": "Optional Updated Name"
}
```

`status` must be: `active` or `unsubscribed`.

### Response

```json
{
    "success": true,
    "message": "Subscriber updated successfully.",
    "data": {
        "id": 1,
        "email": "user@example.com",
        "status": "unsubscribed"
    }
}
```

---

## Send Broadcast Campaign

**POST** `/admin/newsletter/broadcast`

### Request Body

```json
{
    "subject": "New Product Updates",
    "content": "<p>Hello subscribers,</p><p>We just launched new features.</p>",
    "content_format": "html",
    "template": "classic",
    "meta": {
        "segment": "all-active"
    }
}
```

### Content Options

- `content_format`: `plain_text` | `html` (default: `plain_text`)
- `template`: `none` | `classic` | `announcement` | `product_update`
- Image support is handled by your HTML editor output (e.g. `<img src="...">`)

### Response

```json
{
    "success": true,
    "message": "Broadcast completed.",
    "data": {
        "campaign_id": 3,
        "total_recipients": 120,
        "sent_count": 118,
        "failed_count": 2,
        "status": "failed",
        "content_format": "html",
        "template": "classic"
    }
}
```

> `status` is `sent` only when `failed_count` is `0`, otherwise `failed`.

---

## List Campaigns (Paginated)

**GET** `/admin/newsletter/campaigns`

### Query Params

- `page` (optional)
- `per_page` (optional, default `20`)
- `status` (optional): `draft` | `sending` | `sent` | `failed`
- `search` (optional): matches campaign subject

### Response

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 3,
                "subject": "New Product Updates",
                "status": "failed",
                "total_recipients": 120,
                "sent_count": 118,
                "failed_count": 2,
                "sent_at": "2026-02-18T15:15:10.000000Z",
                "created_by": 1
            }
        ],
        "last_page": 1,
        "per_page": 20,
        "total": 1
    }
}
```

---

## Get Available Editor Templates

**GET** `/admin/newsletter/templates`

### Response

```json
{
    "success": true,
    "data": {
        "content_formats": ["plain_text", "html"],
        "templates": [
            {
                "key": "none",
                "name": "No Template",
                "description": "Use only editor content with no extra wrapper."
            },
            {
                "key": "classic",
                "name": "Classic",
                "description": "Simple clean layout for general newsletters."
            },
            {
                "key": "announcement",
                "name": "Announcement",
                "description": "Bold announcement style with clear header section."
            },
            {
                "key": "product_update",
                "name": "Product Update",
                "description": "Sectioned layout for releases, fixes, and highlights."
            }
        ]
    }
}
```

---

## Preview Broadcast Rendering

**POST** `/admin/newsletter/preview`

### Request Body

```json
{
    "subject": "Platform Update",
    "content": "<p>New features and improvements are live.</p>",
    "content_format": "html",
    "template": "product_update",
    "preview_subscriber_name": "Alice"
}
```

### Response

```json
{
    "success": true,
    "data": {
        "subject": "Platform Update",
        "content_format": "html",
        "template": "product_update",
        "rendered_content": "<div>...</div>",
        "email_preview_html": "<!DOCTYPE html>..."
    }
}
```

---

## Campaign Details (with recipient-level results)

**GET** `/admin/newsletter/campaigns/{id}`

### Response

```json
{
    "success": true,
    "data": {
        "id": 3,
        "subject": "New Product Updates",
        "status": "failed",
        "recipients": [
            {
                "id": 45,
                "campaign_id": 3,
                "subscriber_id": 10,
                "status": "sent",
                "error_message": null,
                "sent_at": "2026-02-18T15:14:44.000000Z",
                "subscriber": {
                    "id": 10,
                    "email": "a@example.com",
                    "name": "Alice"
                }
            }
        ]
    }
}
```

---

## 3) Dashboard Stats Integration

`GET /admin/stats` now includes newsletter metrics under:

```json
{
    "data": {
        "newsletter": {
            "subscribers_total": 120,
            "subscribers_active": 118,
            "subscribers_unsubscribed": 2,
            "campaigns_total": 7,
            "campaigns_sent": 6,
            "recent_campaigns": []
        }
    }
}
```

Use this block to build KPI cards and recent broadcast tables in the admin dashboard.

---

## 4) Frontend Recommended Flow

## Public website

1. User submits email in newsletter form.
2. Call `POST /newsletter/subscribe`.
3. Show success toast from `message`.
4. Handle `422` and map validation errors to form fields.

## Admin dashboard

1. Load subscribers table using `GET /admin/newsletter/subscribers`.
2. Hook search/filter/sort controls to query params.
3. Build pagination from Laravel pagination payload.
4. For broadcast modal, submit to `POST /admin/newsletter/broadcast`.
5. For live preview, call `POST /admin/newsletter/preview` on editor changes (debounced).
6. Load template options from `GET /admin/newsletter/templates`.
7. After success, refresh campaigns list and stats widgets.
8. For campaign insights page, call `GET /admin/newsletter/campaigns/{id}`.

---

## 5) TypeScript API Client Example

```ts
import axios from "axios";

const api = axios.create({
    baseURL: "http://127.0.0.1:8000/api",
});

export const subscribeNewsletter = (payload: {
    email: string;
    name?: string;
    source?: string;
}) => api.post("/newsletter/subscribe", payload);

export const unsubscribeNewsletter = (payload: {
    email: string;
    token: string;
}) => api.post("/newsletter/unsubscribe", payload);

export const getSubscribers = (token: string, params?: Record<string, any>) =>
    api.get("/admin/newsletter/subscribers", {
        headers: { Authorization: `Bearer ${token}` },
        params,
    });

export const sendBroadcast = (
    token: string,
    payload: {
        subject: string;
        content: string;
        content_format?: "plain_text" | "html";
        template?: "none" | "classic" | "announcement" | "product_update";
        meta?: Record<string, any>;
    },
) =>
    api.post("/admin/newsletter/broadcast", payload, {
        headers: { Authorization: `Bearer ${token}` },
    });

export const getNewsletterTemplates = (token: string) =>
    api.get("/admin/newsletter/templates", {
        headers: { Authorization: `Bearer ${token}` },
    });

export const previewBroadcast = (
    token: string,
    payload: {
        subject: string;
        content: string;
        content_format?: "plain_text" | "html";
        template?: "none" | "classic" | "announcement" | "product_update";
        preview_subscriber_name?: string;
    },
) =>
    api.post("/admin/newsletter/preview", payload, {
        headers: { Authorization: `Bearer ${token}` },
    });
```

---

## 6) Notes

- Broadcast currently sends to all `active` subscribers.
- Unsubscribe uses secure token + email pairing.
- If SMTP fails for some subscribers, campaign still completes with `failed_count` > 0.
- You can later queue broadcasts for large lists using Laravel jobs/queues.
