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
GET /public/posts/{id}
```

#### Get Published Projects

```http
GET /public/projects?page=1&category=Web Development
```

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

---

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
  "content": "Post content here...",
  "excerpt": "Optional short description",
  "cover_image": "https://example.com/image.jpg",
  "tags": ["web", "tech"],
  "status": "published"
}
```

**Note:** `excerpt` is auto-generated from content if not provided.

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
  "description": "Full description...",
  "category": "Web Development",
  "technologies": ["React", "Node.js"],
  "thumbnail": "https://example.com/thumb.jpg",
  "link": "https://project.com",
  "status": "published"
}
```

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
