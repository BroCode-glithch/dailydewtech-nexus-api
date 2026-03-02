# Additional Modules

- Creative storytelling API docs: see `CREATIVE_API_FRONTEND_GUIDE.md`
- Latest creative admin additions include story/chapter delete + chapter unpublish + published-only admin list endpoint.
- Chapter reader now supports optional long-content paging via `paginate_content`, `page`, `page_size` query params.
- Media/image storage + frontend access contract is documented in the Creative guide under "Image Storage & Access Contract (Frontend)".
- Public fallback media endpoint available: `GET /api/media/{id}` (optional thumbnail: `?variant=thumb`).

# Daily Dew Tech API Documentation

## Base URL

```
http://yourdomain.com/api
```

## Authentication

All authenticated endpoints require a Bearer token:

```
Authorization: Bearer {your-token}
```

---

## Public Endpoints

### Authentication

#### Register

```http
POST /register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Registration successful",
  "token": "1|...",
  "user": {...}
}
```

#### Login

```http
POST /login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**

```json
{
  "success": true,
  "message": "Login successful",
  "token": "2|...",
  "user": {...}
}
```

#### Contact Form

```http
POST /contact
Content-Type: application/json

{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "subject": "Question",
  "message": "Your message here"
}
```

**Rate Limit:** 5 requests per minute

---

### Public Content

#### Get Published Posts

```http
GET /public/posts?page=1&per_page=10&status=published
```

**Query Parameters:**

- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)
- `status` (optional): Filter by status (default: published)
- `search` (optional): Search in title/excerpt/content
- `tag` (optional): Filter by tag
- `sort` (optional): Sort field (default: published_at)
- `direction` (optional): Sort direction (asc/desc, default: desc)

#### Get Single Post

```http
GET /public/posts/{id-or-slug}
```

Optional long-content paging:

```http
GET /public/posts/{id-or-slug}?paginate_content=1&page=1&page_size=1800
```

Query params:

- `paginate_content` (optional boolean, default: `false`)
- `page` (optional integer, default: `1`)
- `page_size` (optional integer chars, default: `1800`, range: `600..5000`)

Behavior:

- Accepts numeric `id` or current `slug`.
- If an old slug is requested (from slug history), API responds with `301` redirect to the current slug URL.
- Post slugs are generated on create and remain stable on normal updates.
- When `paginate_content=1`, `content_html` returns only the requested page chunk and response includes `content_pagination` metadata (`current_page`, `total_pages`, `has_next`, `next_page`, etc.).

#### Get Post Comments (Public)

```http
GET /public/posts/{id-or-slug}/comments?per_page=20
```

Returns paginated top-level comments for the post:

- Top-level comments: `parent_id = null`
- Inline thread count is provided as `replies_count`
- Only `visible` comments are returned publicly

Use the replies endpoint below to load thread replies on demand.

Minimal response shape:

```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 10,
                "body": "Great post!",
                "parent_id": null,
                "replies_count": 3,
                "user": { "id": 5, "name": "Jane", "username": "jane" },
                "children": []
            }
        ]
    }
}
```

#### Get Replies for a Comment Thread (Public, Paginated)

```http
GET /public/posts/comments/{comment_id}/replies?per_page=10&page=1&sort=latest
```

Returns visible replies where `parent_id = {comment_id}`.

This endpoint supports inline “load more replies” UX for long threads.

Query params:

- `per_page` (optional, default: 10)
- `page` (optional, default: 1)
- `sort` (optional): `latest` (default) or `oldest`

**Rich Text Note (TinyMCE):**

- `content` is stored and returned as sanitized HTML.
- Responses now include:
    - `content_html` (ready to render with `dangerouslySetInnerHTML` in React)
    - `content_text` (plain text fallback)
    - `excerpt_text` (plain text excerpt)

#### Get Published Projects

```http
GET /public/projects?page=1&category=Web Development
```

**Rich Text Note (TinyMCE):**

- `description` is stored and returned as sanitized HTML.
- Responses now include:
    - `description_html` (ready-to-render HTML)
    - `description_text` (plain text fallback)

**Query Parameters:**

- `page`, `per_page`, `status`, `search`, `sort`, `direction` (same as posts)
- `category` (optional): Filter by category

#### Get Related Projects

```http
GET /public/projects/{id}/related
```

#### Get Random Quote

```http
GET /public/quotes/random
```

#### Get Public Highlights (Homepage/About stats)

```http
GET /public/highlights
```

**Response:**

```json
{
    "success": true,
    "data": {
        "highlights": [
            {
                "key": "projects_delivered",
                "label": "Projects Delivered",
                "value": "10+",
                "raw_value": 14,
                "unit": null,
                "source": "published_projects_count"
            },
            {
                "key": "client_satisfaction",
                "label": "Client Satisfaction",
                "value": 98,
                "raw_value": 98,
                "unit": "%",
                "source": "configured_service_target"
            },
            {
                "key": "support_availability",
                "label": "Support Availability",
                "value": "24/7",
                "raw_value": "24/7",
                "unit": null,
                "source": "configured_support_window"
            },
            {
                "key": "years_experience",
                "label": "Years of Experience",
                "value": 3,
                "raw_value": 3,
                "unit": "years",
                "source": "derived_from_company_started_year"
            }
        ]
    }
}
```

#### Social Share OG Routes (for React frontend)

> These are **web routes**, not `/api` JSON endpoints.

```http
GET /og
GET /share/projects/{slug-or-id}
GET /share/blog/{slug-or-id}
```

**Why this exists:**

- Social crawlers (Facebook, X/Twitter, LinkedIn, WhatsApp) do not execute client-side React code.
- The Laravel share routes render OG/Twitter meta tags server-side so crawlers can read title, description, and image.
- Human visitors are redirected immediately to the frontend app route after metadata is served.

**Frontend consumption:**

- For homepage sharing, use: `https://your-api-domain/og`
- For project sharing, use: `https://your-api-domain/share/projects/{slug}`
- For blog sharing, use: `https://your-api-domain/share/blog/{slug}`
- Use these URLs in your share buttons/copy-link actions.
- Keep normal app navigation on frontend routes (`/`, `/projects/{id}`, `/blog/{id}`).

**What each route returns:**

- `og:title`, `og:description`, `og:url`, `og:image`
- `twitter:title`, `twitter:description`, `twitter:image`, `twitter:card`
- HTML redirect to frontend route via meta refresh + JS fallback
- Homepage OG includes dynamic project delivery count and company highlights

**Configuration required:**

- `APP_URL` must match your backend public domain.
- `FRONTEND_URL` must match your frontend public domain.
- Project thumbnails / post cover images should be reachable by absolute public URL.
- Optional: place a default OG image at `/public/images/og-default.png` for fallback (used by homepage).

**Quick verification checklist:**

1. Open `https://your-api-domain/og` directly in browser (should redirect to frontend).
2. View page source before redirect completes (should contain OG and Twitter tags with company highlights).
3. Open `https://your-api-domain/share/projects/{slug}` to test project sharing.
4. Test the same URLs in social debuggers (Facebook Share Debugger, Twitter Card Validator) to refresh crawler cache.

---

## Authenticated Blog Comment Endpoints

All endpoints below require:

```http
Authorization: Bearer {token}
```

### Create Comment or Reply

```http
POST /posts/{id-or-slug}/comments
Content-Type: application/json

{
  "body": "This article was very helpful.",
  "parent_id": null
}
```

For replies, send `parent_id` as the target comment id:

```json
{
    "body": "Thanks for sharing this point.",
    "parent_id": 10
}
```

### Edit Own Comment

```http
PATCH /posts/comments/{id}
Content-Type: application/json

{
  "body": "Updated comment text"
}
```

### Delete Comment

```http
DELETE /posts/comments/{id}
```

Authorization rules:

- Comment owner can delete own comment
- `admin | super_admin | moderator | editor` can delete any blog comment
- Other users get `403 Forbidden`

## Admin Endpoints

### Admin Authentication

#### Request Login Code

```http
POST /admin/login/request-code
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

**Rate Limit:** 5 requests per minute

#### Verify Login Code

```http
POST /admin/login/verify-code
Content-Type: application/json

{
  "email": "admin@example.com",
  "code": "123456"
}
```

---

### Protected Routes (Require Authentication)

#### Logout

```http
POST /logout
Authorization: Bearer {token}
```

#### Get Current User

```http
GET /user
Authorization: Bearer {token}
```

---

### Dashboard

#### Get Statistics

```http
GET /admin/stats
Authorization: Bearer {token}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "posts": {
      "total": 10,
      "published": 8,
      "drafts": 2,
      "recent": [...]
    },
    "messages": {
      "total": 50,
      "unread": 5,
      "read": 45,
      "recent": [...]
    },
    "projects": {
      "total": 15,
      "published": 12,
      "drafts": 3,
      "recent": [...]
    },
    "users": {
      "total": 5,
      "admins": 2
    },
    "activity": {
      "new_posts_this_week": 3,
      "new_messages_this_week": 10,
      "new_projects_this_week": 2
    }
  }
}
```

---

### Posts Management

#### List All Posts (Admin)

```http
GET /admin/posts?page=1&status=all
Authorization: Bearer {token}
```

#### Create Post

```http
POST /admin/posts
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "My Post Title",
  "content": "<p><strong>Formatted post</strong> content from TinyMCE...</p>",
  "excerpt": "Optional short description",
  "cover_image": "https://example.com/image.jpg",
  "tags": ["web", "tech"],
  "status": "published"
}
```

**Note:** `excerpt` is auto-generated from content if not provided.
**Note:** `content` supports HTML from TinyMCE and is sanitized server-side before storage.

#### Update Post

```http
PUT /admin/posts/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title",
  "status": "draft"
}
```

#### Delete Post

```http
DELETE /admin/posts/{id}
Authorization: Bearer {token}
```

#### Publish/Unpublish Post

```http
POST /admin/posts/{id}/publish
POST /admin/posts/{id}/unpublish
Authorization: Bearer {token}
```

---

### Projects Management

#### List All Projects (Admin)

```http
GET /admin/projects?page=1
Authorization: Bearer {token}
```

#### Create Project

```http
POST /admin/projects
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "E-Commerce Platform",
  "description": "<p>Full <em>formatted</em> description from TinyMCE...</p>",
  "category": "Web Development",
  "technologies": ["React", "Node.js"],
  "thumbnail": "https://example.com/thumb.jpg",
  "link": "https://project.com",
  "status": "published"
}
```

**Note:** `description` supports HTML from TinyMCE and is sanitized server-side before storage.

#### Update/Delete Project

```http
PUT /admin/projects/{id}
DELETE /admin/projects/{id}
Authorization: Bearer {token}
```

---

### Messages Management

#### List Messages

```http
GET /admin/messages?page=1&status=unread
Authorization: Bearer {token}
```

#### Get Single Message

```http
GET /admin/messages/{id}
Authorization: Bearer {token}
```

#### Update Message (e.g., mark as read)

```http
PUT /admin/messages/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "read"
}
```

#### Delete Message (Soft Delete)

```http
DELETE /admin/messages/{id}
Authorization: Bearer {token}
```

#### Get Trashed Messages

```http
GET /admin/messages/trashed
Authorization: Bearer {token}
```

#### Restore Message

```http
POST /admin/messages/{id}/restore
Authorization: Bearer {token}
```

#### Permanently Delete Message

```http
DELETE /admin/messages/{id}/force
Authorization: Bearer {token}
```

---

### Quotes Management

#### List All Quotes

```http
GET /admin/quotes?page=1&per_page=15
Authorization: Bearer {token}
```

#### Generate & Save New Quote

```http
GET /admin/quotes/inspire
Authorization: Bearer {token}
```

---

### Newsletter Management

#### Get Editor Templates and Supported Formats

```http
GET /admin/newsletter/templates
Authorization: Bearer {token}
```

#### Preview Broadcast Rendering

```http
POST /admin/newsletter/preview
Authorization: Bearer {token}
Content-Type: application/json

{
  "subject": "Product Update",
  "content": "<p>We shipped new improvements.</p>",
  "content_format": "html",
  "template": "product_update",
  "preview_subscriber_name": "John"
}
```

#### Send Broadcast (Plain Text or HTML)

```http
POST /admin/newsletter/broadcast
Authorization: Bearer {token}
Content-Type: application/json

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

---

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Optional success message",
  "data": {...}
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "errors": {...}
}
```

### Validation Error Response

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "field_name": ["Error message 1", "Error message 2"]
    }
}
```

---

## Rate Limiting

- **Authentication Endpoints:** 10 requests per minute
- **Contact Form:** 5 requests per minute
- **Admin Authentication:** 5 requests per minute
- **Other Endpoints:** No limit (protected by authentication)

---

## Error Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests (Rate Limit)
- `500` - Server Error

---

## Pagination

Most list endpoints support pagination:

```http
GET /endpoint?page=2&per_page=20
```

**Response:**

```json
{
  "success": true,
  "data": {
    "current_page": 2,
    "data": [...],
    "first_page_url": "...",
    "from": 16,
    "last_page": 5,
    "last_page_url": "...",
    "next_page_url": "...",
    "path": "...",
    "per_page": 15,
    "prev_page_url": "...",
    "to": 30,
    "total": 75
  }
}
```
