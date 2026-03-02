# Creative API Frontend Guide (MVP)

Base URL: `/api`

Main module prefix: `/creative`

Auth: Laravel Sanctum Bearer token for protected endpoints.

**Security Note**: In production (`APP_DEBUG=false`), all database errors return generic messages without schema details. See `CREATIVE_SECURITY.md` for complete security documentation.

## Status + Roles

- Story status: `draft | pending | published | archived`
- Chapter status: `draft | pending | published`
- User roles supported in backend checks: `super_admin | admin | editor | author | moderator | user`

## Admin + Author Portal Separation Contract (2026-02-27)

### Portal Routing Source of Truth

Frontend should route by authenticated `user.role`:

- `admin | super_admin` → Admin portal
- `author` → Author portal
- `user | moderator | editor` → Public/reader experience (unless you intentionally expose extra UI)

### Stable Auth Identity Fields

Use `GET /api/user` (or `GET /api/auth/me`) as canonical session identity check.

Required frontend fields (always read from returned user object):

- `id`
- `name`
- `email`
- `role` (`user | author | admin | super_admin | moderator | editor`)
- `status` (`active | suspended | banned`)

### Route Protection Contract

- `/admin/*` routes: `admin` middleware (admin/super_admin)
- `/author/*` routes: `role:author,editor,admin,super_admin`
- Public creative routes remain open

HTTP behavior:

- `401` unauthenticated (missing/invalid token)
- `403` authenticated but role not permitted

### Response Envelope (Creative APIs)

Success:

```json
{
    "success": true,
    "message": "optional",
    "data": {}
}
```

Validation error (422):

```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "field": ["Error message"]
    }
}
```

Permission/auth errors:

```json
{
    "message": "Forbidden"
}
```

## User Authentication & Portal System

### Overview

Users need to **register and login** to engage with stories (like, bookmark, comment, create content). Public readers can browse without an account, but all engagement features require authentication.

### What Requires Login?

**Public (No Login Required):**

- Browse stories, read chapters
- View categories, tags, trending
- View comments (read-only)

**Requires User Login:**

- Like stories/chapters
- Bookmark stories
- Track reading progress
- Post/edit/delete comments
- Create reports

**Requires Author Role:**

- Create/edit stories
- Create/edit chapters
- Submit for publishing

**Requires Admin Role:**

- Approve/publish stories
- Moderate comments
- Manage users

### Registration

`POST /api/register` or `POST /api/auth/register`

**Request Body:**

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201):**

```json
{
    "message": "Registration successful",
    "token": "3|abc123xyz...",
    "user": {
        "id": 15,
        "name": "John Doe",
        "email": "john@example.com",
        "username": null,
        "avatar": null,
        "bio": null,
        "role": "user",
        "status": "active",
        "created_at": "2026-02-22T10:30:00.000000Z"
    }
}
```

**Frontend Implementation:**

```javascript
const response = await fetch("/api/register", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ name, email, password, password_confirmation }),
});

const { token, user } = await response.json();
localStorage.setItem("auth_token", token);
localStorage.setItem("user", JSON.stringify(user));
```

### Login

`POST /api/login` or `POST /api/auth/login`

**Request Body:**

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (200):**

```json
{
    "message": "Login successful",
    "token": "4|def456uvw...",
    "user": {
        /* same user object */
    }
}
```

**Frontend Implementation:**

```javascript
const response = await fetch("/api/login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, password }),
});

const { token, user } = await response.json();
localStorage.setItem("auth_token", token);
localStorage.setItem("user", JSON.stringify(user));
```

### Using the Token

All authenticated requests require the Bearer token in headers:

```javascript
const token = localStorage.getItem("auth_token");

const response = await fetch("/api/likes", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({ type: "story", id: 5 }),
});
```

### Get Current User

`GET /api/user` or `GET /api/auth/me`

Headers: `Authorization: Bearer {token}`

**Response:**

```json
{
    "id": 15,
    "name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "avatar": "/storage/avatars/15.jpg",
    "bio": "Story enthusiast",
    "role": "author",
    "status": "active"
}
```

Use this to:

- Check if token is still valid
- Get updated user info
- Verify role/permissions

### Logout

`POST /api/logout` or `POST /api/auth/logout`

Headers: `Authorization: Bearer {token}`

**Response:**

```json
{
    "message": "Logged out successfully"
}
```

**Frontend:**

```javascript
await fetch("/api/logout", {
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
});

localStorage.removeItem("auth_token");
localStorage.removeItem("user");
```

## User Engagement Dashboard + Notifications (Auth Required)

### User Dashboard

`GET /creative/me/dashboard`

Returns engagement counts, recent comments, and unread notification count.

### Notifications

- `GET /creative/me/notifications?per_page=20`
- `POST /creative/me/notifications/{id}/read`
- `POST /creative/me/notifications/read-all`

Notifications are delivered **in-app and email**. Current triggers:

- Reply to your comment
- Comment on your story
- Author request status updates
- Story publish/unpublish events (author only)

## Author Request Workflow (Auth Required)

### Submit Author Request

`POST /author/request`

```json
{
    "bio": "Short bio...",
    "sample_link": "https://example.com/my-writing-sample",
    "accepted_terms": true,
    "accepted_privacy": true,
    "accepted_ip_policy": true,
    "accepted_community_guidelines": true
}
```

**Required consents**

- All four consent flags must be `true` or the request is rejected with 422.
- The backend stores policy versions and acceptance evidence (timestamp, IP, user agent).

Expected behavior:

- Any missing/false acceptance flag returns `422`.
- If an existing pending request exists, API returns `409` with existing request payload.

**Policy versioning**

- Versions are stored in `policy_versions` with active rows per `policy_key`.
- Current active default version is `2026-02-22` for: `terms`, `privacy`, `ip_policy`, `community_guidelines`.
- The frontend should display policy version/date near the consent text.

### Check Request Status

`GET /author/request`

Returns latest request record in `data`.

Frontend status mapping:

- `data = null` → `none`
- `data.status = pending | approved | rejected`

Recommended frontend fields to read:

- `id`, `user_id`, `status`, `bio`, `sample_link`, `admin_notes`, `reviewed_at`, `created_at`, `updated_at`

### Admin Review Endpoints

- `GET /admin/creative/author-requests?status=pending&page=1&per_page=20`
- `PATCH /admin/creative/author-requests/{id}`
- `GET /admin/creative/stories?status=all&page=1&per_page=20`
- `GET /admin/creative/stories/published?page=1&per_page=20`
- `GET /admin/creative/stories/{id}`
- `POST /admin/creative/stories/{id}/publish`
- `POST /admin/creative/stories/{id}/unpublish`
- `DELETE /admin/creative/stories/{id}`
- `POST /admin/creative/chapters/{id}/publish`
- `POST /admin/creative/chapters/{id}/unpublish`
- `DELETE /admin/creative/chapters/{id}`

Patch payload:

```json
{
    "status": "approved",
    "admin_notes": "optional"
}
```

Required behavior implemented:

- `approved` updates request status and sets request review metadata.
- On approval, backend automatically sets `user.role = author`.
- Backend emits user notification for approve/reject.

### Unpublished Story Retrieval (Admin Republish Flow)

Yes — unpublished stories are retrievable for admin UI.

Use:

- `GET /admin/creative/stories?status=draft`
- `GET /admin/creative/stories?status=pending`
- `GET /admin/creative/stories?status=archived`
- `GET /admin/creative/stories?status=all`

Then open details:

- `GET /admin/creative/stories/{id}`

Republish action:

- `POST /admin/creative/stories/{id}/publish`

Notes:

- Public endpoint `GET /creative/stories/{slug}` only returns published stories.
- Unpublished/draft stories should be loaded from admin endpoints above.

### Professional Admin Story Operations (Recommended Frontend Flow)

Use this sequence for a robust admin portal:

1. Fetch status badges/counters:
    - `GET /admin/creative/stories/status-summary`
2. Load table/grid by status:
    - `GET /admin/creative/stories?status=all&page=1&per_page=20`
    - `GET /admin/creative/stories?status=draft`
3. Apply search/filter/sort:
    - `search`, `author_id`, `sort=latest|oldest|published_at`, `direction=asc|desc`
4. Open detail panel/page:
    - `GET /admin/creative/stories/{id}`
    - `GET /admin/creative/stories/by-slug/{slug}`
5. Action workflow:
    - Unpublish: `POST /admin/creative/stories/{id}/unpublish`
    - Republish: `POST /admin/creative/stories/{id}/publish`
    - Delete story: `DELETE /admin/creative/stories/{id}`
    - Publish chapter: `POST /admin/creative/chapters/{id}/publish`
    - Unpublish chapter: `POST /admin/creative/chapters/{id}/unpublish`
    - Delete chapter: `DELETE /admin/creative/chapters/{id}`

Publish/unpublish responses include:

- `success`
- `data` (updated story)
- `notification_delivered` (boolean)

### User Profile Fields

Extended user fields for creative platform:

- `username` (unique, nullable) - display name for stories
- `avatar` (URL) - profile picture
- `bio` (text) - author bio shown on stories
- `status` (enum) - `active | suspended | banned`
- `role` (enum) - `user | author | editor | moderator | admin | super_admin`
- `last_login_at` (timestamp)
- `two_factor_enabled` (boolean)

### Upgrading to Author

Users can request author status from their profile. Admins can grant it via:

`PATCH /api/admin/creative/users/{id}`

```json
{
    "role": "author"
}
```

### Author Portal APIs (Role-Guarded)

- `GET /author/dashboard`
- `GET /author/stories`
- `POST /author/stories`
- `PATCH /author/stories/{id}`
- `DELETE /author/stories/{id}`
- `POST /author/stories/{id}/submit`
- `GET /author/stories/{id}/chapters`
- `POST /author/stories/{id}/chapters`
- `PATCH /author/chapters/{id}`
- `POST /author/chapters/{id}/submit`

Ownership rule:

- Authors are scoped to their own stories/chapters.
- Admin roles can access via elevated permissions.

### Frontend Auth Flow Example

```javascript
// Check auth on app load
const token = localStorage.getItem('auth_token');
if (token) {
  try {
    const response = await fetch('/api/user', {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (response.ok) {
      const user = await response.json();
      // User is authenticated
      setUser(user);
    } else {
      // Token invalid, clear storage
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
    }
  } catch (error) {
    console.error('Auth check failed:', error);
  }
}

// Protect engagement features
function handleLike(storyId) {
  if (!token) {
    // Redirect to login
    navigate('/login');
    return;
  }

  // User is logged in, proceed
  await likeStory(storyId);
}
```

## Public Reader Endpoints

### Browse Stories

`GET /creative/stories`

Query params:

- `search` (title/summary/description)
- `category` (category slug)
- `tag` (tag slug)
- `sort=latest|trending`
- `per_page` (default 12)

Returns paginated published stories with:

- author basic profile
- cover media
- counts (`likes_count`, `bookmarks_count`, `views_count`)

### Story Detail

`GET /creative/stories/{slug}`

Returns story + published chapter list + categories/tags + engagement counts.

Preview behavior for non-published stories:

- Anonymous users: published-only (draft/pending/archived returns 404)
- Authenticated owner (`author_id`) or `admin|super_admin|editor`: can open non-published story by slug
- For author/admin studio preview, always send `Authorization: Bearer {token}`

### Chapter Reader

`GET /creative/stories/{slug}/chapters/{chapterNumber}`

Returns:

- `story` object
- `chapter` object (sanitized HTML in `content_html`)
- `navigation.previous` and `navigation.next`

Optional long-content paging query params:

- `paginate_content` (optional boolean, default: `false`)
- `page` (optional integer, default: `1`)
- `page_size` (optional integer chars, default: `1800`, range `600..5000`)

When paging is enabled, response includes:

- `content_pagination.current_page`
- `content_pagination.total_pages`
- `content_pagination.has_previous`
- `content_pagination.has_next`
- `content_pagination.previous_page`
- `content_pagination.next_page`

Example:

`GET /creative/stories/{slug}/chapters/{chapterNumber}?paginate_content=1&page=2&page_size=1800`

Preview behavior for non-published chapters follows the same auth rule as Story Detail above.

### OG Metadata + Image (Public)

These endpoints are used by the frontend or SSR layer to fetch per-story/chapter metadata and OG images.

- `GET /creative/og/story/{slug}`
- `GET /creative/og/story/{slug}/image` (PNG)
- `GET /creative/og/story/{slug}/chapter/{chapterNumber}`
- `GET /creative/og/story/{slug}/chapter/{chapterNumber}/image` (PNG)

### Taxonomy

- `GET /creative/categories`
- `GET /creative/tags`

### Trending

`GET /creative/trending?days=7`

Backend score currently uses:

- `views_window * 1 + likes_window * 3`

### View Tracking

`POST /creative/stories/{id}/view`

Body:

```json
{
    "chapter_id": 12
}
```

`chapter_id` is optional.

## Engagement Endpoints (Auth Required)

### Likes

- `POST /likes`

```json
{
    "type": "story",
    "id": 14
}
```

- `DELETE /likes/{type}/{id}` where `type` is `story` or `chapter`

### Bookmarks

- `POST /bookmarks`

```json
{
    "story_id": 14
}
```

- `DELETE /bookmarks/{story_id}`

### Reading Progress

`POST /reading-progress`

```json
{
    "story_id": 14,
    "chapter_id": 66,
    "progress": 72
}
```

`progress` range: `0..100`

## Comments + Reports

### Read Comments (Public)

`GET /creative/comments?type=chapter&id=66&per_page=20`

**Query Parameters:**

- `type` - required: `story` or `chapter`
- `id` - required: story or chapter ID
- `per_page` - optional: default 20

Returns paginated comments with nested replies (parent/child structure).

### Create Comment (Auth Required)

`POST /comments`

**Headers:** `Authorization: Bearer {token}`

**Request Body:**

```json
{
    "type": "chapter",
    "id": 66,
    "body": "Loved this chapter!",
    "parent_id": null
}
```

**Fields:**

- `type` (required): `story` or `chapter`
- `id` (required): story or chapter ID
- `body` (required): comment text, max 5000 characters
- `parent_id` (optional): ID of parent comment for replies

**Response (201):**

```json
{
    "success": true,
    "data": {
        "id": 42,
        "user_id": 15,
        "body": "Loved this chapter!",
        "status": "visible",
        "created_at": "2026-02-22T10:30:00.000000Z"
    }
}
```

### Edit Comment (Auth Required - Owner Only)

`PATCH /comments/{id}`

**Headers:** `Authorization: Bearer {token}`

**Authorization:** User must be the comment **owner** (user_id matches)

**Request Body:**

```json
{
    "body": "Updated comment text"
}
```

**Response (200):**

```json
{
    "success": true,
    "data": {
        "id": 42,
        "body": "Updated comment text",
        "updated_at": "2026-02-22T10:35:00.000000Z"
    }
}
```

**Error (403 Forbidden):**

```json
{
    "message": "Forbidden"
}
```

**Frontend Example:**

```javascript
// Show edit button only for comment owner
{
    comment.user_id === currentUser.id && (
        <button onClick={() => editComment(comment.id)}>Edit</button>
    );
}

// Edit request
async function editComment(commentId, newBody) {
    const response = await fetch(`/api/comments/${commentId}`, {
        method: "PATCH",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ body: newBody }),
    });

    if (response.status === 403) {
        alert("You can only edit your own comments");
    }
}
```

### Delete Comment (Auth Required - Owner or Moderator)

`DELETE /comments/{id}`

**Headers:** `Authorization: Bearer {token}`

**Authorization:**

- ✅ User is the comment **owner**, OR
- ✅ User has **admin/moderator/editor** role

**Response (200):**

```json
{
    "success": true,
    "message": "Comment deleted"
}
```

**Error (403 Forbidden):**

```json
{
    "message": "Forbidden"
}
```

**Frontend Example:**

```javascript
// Show delete button for owner or moderators
const canDelete =
    comment.user_id === currentUser.id ||
    ["admin", "super_admin", "moderator", "editor"].includes(currentUser.role);

{
    canDelete && (
        <button onClick={() => deleteComment(comment.id)}>Delete</button>
    );
}

// Delete request
async function deleteComment(commentId) {
    const response = await fetch(`/api/comments/${commentId}`, {
        method: "DELETE",
        headers: { Authorization: `Bearer ${token}` },
    });

    if (response.ok) {
        // Remove comment from UI
        removeCommentFromList(commentId);
    }
}
```

### Create Report (Auth Required)

`POST /reports`

```json
{
    "type": "comment",
    "id": 105,
    "reason": "abuse",
    "notes": "optional notes"
}
```

`type` values: `story | chapter | comment | user`

## Author Endpoints (Auth + role)

Prefix: `/author`

Allowed roles by middleware: `author, editor, admin, super_admin`

- `GET /author/dashboard`
- `GET /author/stories`
- `GET /author/stories/{id}`
- `GET /author/stories/{id}/chapters`
- `POST /author/stories/import-docx` (sync DOCX import, throttled)
- `GET /author/stories/import-docx/jobs/{jobId}` (import status by `import_reference`)
- `POST /author/stories`
- `PATCH /author/stories/{id}`
- `DELETE /author/stories/{id}`
- `POST /author/stories/{id}/submit`
- `POST /author/stories/{id}/chapters`
- `PATCH /author/chapters/{id}`
- `POST /author/chapters/{id}/submit`

Notes:

- chapter content must be sent in `content_html`
- backend computes `word_count` + `read_time_minutes`
- HTML is sanitized server-side
- story delete is permanent (hard delete). Use admin unpublish flow if you need reversible removal from public view.

### Story Genres (Taxonomy for Search + Discovery)

Yes — genre selection is implemented via taxonomy fields on story create/update:

- `category_ids` (array of category ids): primary genre buckets
- `tag_ids` (array of tag ids): sub-genres/themes/labels

Create/update payload example:

```json
{
    "title": "The Midnight Echo",
    "summary": "A mystery in Lagos nights",
    "description": "Long form description...",
    "cover_image_id": 10,
    "language": "en",
    "visibility": "public",
    "category_ids": [1, 4],
    "tag_ids": [2, 5]
}
```

How frontend uses this:

- Load taxonomy options: `GET /creative/categories`, `GET /creative/tags`
- Filter stories by genre/tag in public listing:
    - `GET /creative/stories?category={categorySlug}`
    - `GET /creative/stories?tag={tagSlug}`

### Story Images and OG/SEO Usage

Yes — story images are part of SEO/share metadata flow.

- Story card/detail image source: `cover_image_id` → `coverImage`
- Chapter reader image source: `featured_image_id` (chapter-level)
- OG metadata/image endpoints use these media fields:
    - Story OG uses story `coverImage`
    - Chapter OG uses chapter `featuredImage`; falls back to story `coverImage`

Media object contract (applies to `coverImage` and `featuredImage` in story/chapter payloads):

- `id`
- `path`
- `thumbnail_path`
- `url`
- `thumbnail_url`
- `api_url`
- `api_thumbnail_url`
- `preferred_image_url` (recommended)
- `preferred_thumbnail_url` (recommended)

Example `coverImage` object in story payload:

```json
{
    "id": 10,
    "path": "creative/uploads/abc.jpg",
    "thumbnail_path": "creative/uploads/thumbs/abc-thumb.jpg",
    "url": "http://127.0.0.1:8000/storage/creative/uploads/abc.jpg",
    "thumbnail_url": "http://127.0.0.1:8000/storage/creative/uploads/thumbs/abc-thumb.jpg",
    "api_url": "http://127.0.0.1:8000/api/media/10",
    "api_thumbnail_url": "http://127.0.0.1:8000/api/media/10?variant=thumb",
    "preferred_image_url": "http://127.0.0.1:8000/api/media/10",
    "preferred_thumbnail_url": "http://127.0.0.1:8000/api/media/10?variant=thumb"
}
```

Frontend image binding recommendation:

```ts
const imageSrc = item?.coverImage?.preferred_image_url
    ?? item?.coverImage?.api_url
    ?? item?.coverImage?.url
    ?? null;

const thumbSrc = item?.coverImage?.preferred_thumbnail_url
    ?? item?.coverImage?.api_thumbnail_url
    ?? item?.coverImage?.thumbnail_url
    ?? imageSrc;
```

OG endpoints:

- `GET /creative/og/story/{slug}`
- `GET /creative/og/story/{slug}/image` (PNG)
- `GET /creative/og/story/{slug}/chapter/{chapterNumber}`
- `GET /creative/og/story/{slug}/chapter/{chapterNumber}/image` (PNG)

Frontend recommendation:

1. Upload image via `POST /media/upload`
2. Save returned `media_id` as `cover_image_id` (story) or `featured_image_id` (chapter)
3. Use OG metadata endpoints in share previews/SSR metadata generation

## Media Upload (Auth)

`POST /media/upload` as `multipart/form-data`

Fields:

- `file` (required): `jpg|jpeg|png|webp`, max `10MB`
    - Compatibility aliases also accepted: `image`, `cover_image`, `coverImage`, `featured_image`, `featuredImage`
- `alt_text` (optional)

Server requirement for this limit:

- PHP `upload_max_filesize` and `post_max_size` must be set above `10MB` (recommended `12M` or higher).
- If uploads around `2MB+` fail, your server is likely still on default `upload_max_filesize=2M`.
- Use `GET /media/upload-limits` to verify active runtime limits.

Response:

```json
{
    "success": true,
    "data": {
        "media_id": 10,
        "url": "/storage/creative/uploads/...",
        "thumbnail_url": "/storage/creative/uploads/thumbs/...",
        "api_url": "http://127.0.0.1:8000/api/media/10",
        "api_thumbnail_url": "http://127.0.0.1:8000/api/media/10?variant=thumb",
        "preferred_image_url": "http://127.0.0.1:8000/api/media/10",
        "preferred_thumbnail_url": "http://127.0.0.1:8000/api/media/10?variant=thumb",
        "media": {}
    }
}
```

Recommended frontend rendering source priority:

1. `preferred_image_url` / `preferred_thumbnail_url` (recommended default)
2. `api_url` / `api_thumbnail_url`
3. `url` / `thumbnail_url` (works when `/storage` static serving is correctly configured)

Note: these `preferred_*` fields are now included in media objects returned by story/chapter endpoints (e.g., `coverImage`, `featuredImage`) in addition to upload response payloads.

Public API fallback endpoint:

- `GET /media/{id}`
- `GET /media/{id}?variant=thumb`

Why this matters:

- If frontend runs on `http://127.0.0.1:8080` and requests `/storage/...` on that same origin, it may hit frontend server/Vite instead of backend storage and return `403`.
- `api_url` always points to backend API origin and avoids that mismatch.

### Upload Limit Diagnostics (Auth)

Use this endpoint to debug server-side upload limits quickly:

- `GET /media/upload-limits`

Response includes:

- PHP `upload_max_filesize`
- PHP `post_max_size`
- Backend app upload cap (`10MB`)
- Effective max bytes/MB after all limits are applied

### Media URL/Path Health Diagnostics (Auth)

Use this endpoint to debug why an uploaded image is not loading in frontend:

- `GET /media/health?media_id={id}`
- `GET /media/health?path=/storage/creative/uploads/your-file.jpg`

You can provide either:

- `media_id` (checks DB media record paths), or
- `path` (checks a direct URL/path string)

Response includes:

- whether file exists on public disk (`exists_on_disk`)
- filesystem path used by backend (`absolute_file_path`)
- generated relative and absolute URLs (`relative_url`, `absolute_url`)
- storage symlink check (`storage_symlink_exists`)
- `APP_URL` + public disk URL values used for URL generation

Use this to quickly detect:

- missing file on disk
- broken `public/storage` symlink
- wrong URL host/port mismatch between frontend and backend

### Image Storage & Access Contract (Frontend)

Where files are stored on backend:

- Original files: `storage/app/public/creative/uploads/...`
- Thumbnails: `storage/app/public/creative/uploads/thumbs/...`

How files are exposed publicly:

- Public web path prefix: `/storage/...`
- Laravel symlink must exist: `public/storage -> storage/app/public`
- Ensure this has been run on backend host: `php artisan storage:link`

Media upload response fields:

- `data.url` → original image URL/path
- `data.thumbnail_url` → thumbnail URL/path
- `data.api_url` → backend API-streamed original image URL
- `data.api_thumbnail_url` → backend API-streamed thumbnail image URL

Important for frontend URL building:

- Backend may return a relative path (example: `/storage/creative/uploads/abc.jpg`).
- If frontend and API are on different origins, always resolve image URL against API origin.

Example:

```ts
const apiOrigin = "http://127.0.0.1:8000"; // your backend origin

function resolveMediaUrl(pathOrUrl?: string | null) {
    if (!pathOrUrl) return null;
    if (/^https?:\/\//i.test(pathOrUrl)) return pathOrUrl;
    return new URL(pathOrUrl, apiOrigin).toString();
}

const coverSrc = resolveMediaUrl(media.url);
const thumbSrc = resolveMediaUrl(media.thumbnail_url);
```

Environment alignment checklist (very important):

1. `APP_URL` should match the backend URL used by frontend requests.
2. If using `php artisan serve`, frontend should use that same origin (commonly `http://127.0.0.1:8000`).
3. If using WAMP/Apache host, frontend should use that Apache host for API and image URLs.
4. Do not mix API origin and image origin across different runtime servers unless explicitly configured.

Quick debugging checklist when images do not load:

1. Open returned `url` directly in browser.
2. If 404, run `php artisan storage:link` and confirm file exists in `storage/app/public/creative/uploads`.
3. If URL points to wrong host/port, fix frontend URL resolver and/or `APP_URL`.
4. If upload succeeds but URL 403/blocked, check web server static file permissions for `public/storage`.
5. Use `GET /media/upload-limits` to confirm runtime upload limits are correct for the server receiving requests.

## Word Import (DOCX → Story/Chapters)

Status: **Implemented (Sync MVP)**.

Goal:

- Allow authors to upload `.docx` and generate draft story + draft chapters for review in Author Studio.

Roles:

- `author, editor, admin, super_admin` (auth required)

### Primary Endpoint (Sync Preview)

`POST /author/stories/import-docx`

`multipart/form-data` fields:

- `file` (required): `.docx`, recommended max `10MB` (MVP)
- `story_title` (optional)
- `category_ids[]` (optional)
- `tag_ids[]` (optional)
- `import_mode` (optional): `single_chapter | split_by_headings` (default: `split_by_headings`)

Expected success envelope (`200`):

```json
{
    "success": true,
    "message": "Document parsed successfully",
    "data": {
        "story": {
            "id": 123,
            "title": "The Midnight Echo",
            "status": "draft",
            "category_ids": [1, 4],
            "tag_ids": [2, 5]
        },
        "chapters": [
            {
                "id": 501,
                "chapter_number": 1,
                "title": "Chapter 1: Arrival",
                "word_count": 1450,
                "read_time_minutes": 6,
                "status": "draft"
            }
        ],
        "preview": {
            "story_html_sample": "<p>...</p>",
            "chapter_html_samples": ["<h2>...</h2><p>...</p>"]
        },
        "warnings": ["unsupported formatting simplified"],
        "import_reference": "imp_01J..."
    }
}
```

Validation error (`422`) shape:

```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "file": ["The file must be a DOCX document."]
    }
}
```

### Job Status Endpoint

`GET /author/stories/import-docx/jobs/{jobId}`

Current MVP behavior:

- Import runs synchronously on upload endpoint.
- Backend still records and exposes import job status via `import_reference`.
- Typical statuses for MVP: `processing | completed | failed`.

Response shape:

```json
{
    "success": true,
    "data": {
        "job_id": "imp_xxx",
        "status": "completed",
        "story": {
            "id": 123,
            "title": "The Midnight Echo",
            "status": "draft",
            "slug": "the-midnight-echo"
        },
        "warnings": [],
        "errors": [],
        "created_at": "2026-02-27T18:00:00.000000Z",
        "updated_at": "2026-02-27T18:00:02.000000Z"
    }
}
```

### Parsing and Safety Rules (Contract)

- Story status from import is always `draft`
- Chapter status from import is always `draft`
- Chapter split default: heading-based (`Chapter X`, H1/H2), fallback single chapter
- Sanitize generated HTML before DB write
- Extract supported images and persist via media pipeline
- Return warnings for unsupported formatting/image failures
- Derive `word_count` and `read_time_minutes` server-side

Current MVP notes:

- Supports `.docx` only, max file size `10MB`.
- Import mode supports `single_chapter` and `split_by_headings`.
- Heading-based split uses chapter-like headings and top heading levels.
- At least one draft chapter is created even if source formatting is weak.

### Frontend Integration Flow (Planned)

1. Upload `.docx`
2. Receive created draft story/chapter ids + preview + warnings
3. Open returned ids in Author Studio editor
4. Author reviews/edits, then submits via existing `/author/stories/{id}/submit` flow

### Current State Note

- Existing endpoints for manual story/chapter create remain active.
- Word import section above is the agreed contract for upcoming backend implementation.

## Admin Creative Endpoints (Auth + admin middleware)

Prefix: `/admin/creative`

- `GET /dashboard`
- `GET /stories/status-summary` (counts by `draft|pending|published|archived|all`)
- `GET /stories` (supports `status=all|draft|pending|published|archived`, `search`, `author_id`, `per_page`)
- `GET /stories/by-slug/{slug}` (admin detail lookup by slug)
- `GET /stories/{id}` (includes chapters/categories/tags)
- `GET /users`
- `PATCH /users/{id}` (supports `role`, `status`)
- `DELETE /users/{id}` (admin only)
- `GET /author-requests`
- `PATCH /author-requests/{id}` (approve/reject)
- `POST /stories/{id}/publish` (returns `notification_delivered`)
- `POST /stories/{id}/unpublish` (returns `notification_delivered`)
- `POST /chapters/{id}/publish`
- `POST /moderation/comments/{id}/hide`
- `GET /reports`
- `POST /reports/{id}/resolve`

Also added for role system extension:

- `POST /roles`
- `POST /permissions`

If Spatie is not installed in environment, these currently return `501` with a clear message.

## Frontend Route Mapping (React)

- `/creative` landing: use `GET /creative/trending` + `GET /creative/stories?sort=latest`
- `/creative/stories`: use `GET /creative/stories` with filters
- `/creative/story/:slug`: use `GET /creative/stories/{slug}`
- `/creative/story/:slug/chapter/:number`: use chapter reader endpoint + call view tracker
- author dashboard/edit screens: use `/author/*` endpoints
- admin creative screens: use `/admin/creative/*` endpoints

## Security Implemented

- Sanitization on chapter save and on chapter read output
- Upload MIME and size restrictions
- Rate limits on login, comments, reports, likes, uploads
- Role and ownership checks in author/admin controllers
- Audit logs for sensitive admin actions (publish/unpublish, moderation, role/status changes)

## Known Environment Note

`spatie/laravel-permission` install failed in this local environment during implementation, so role checks currently run via `users.role` + middleware/controller guards. Endpoints for creating roles/permissions are already scaffolded and auto-activate once the package is installed.
