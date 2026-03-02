# Creative API Quick Test Guide

## Test Data Available

After running `php artisan db:seed --class=CreativeSeeder`, you have:

- **2 published stories** with chapters
- **5 categories**: Fiction, Sci-Fi, Fantasy, Mystery, Romance
- **5 tags**: Adventure, Drama, Humor, Action, Suspense
- **Author account**: `author@example.com` / `password`

## Quick Test Requests

### User Authentication Tests

#### Register New User

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Reader",
    "email": "reader@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Expected Response:**

```json
{
    "message": "Registration successful",
    "token": "5|abc123xyz...",
    "user": {
        "id": 3,
        "name": "Test Reader",
        "email": "reader@example.com",
        "role": "user",
        "status": "active"
    }
}
```

Save the token for authenticated requests!

#### Login Existing User

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "author@example.com",
    "password": "password"
  }'
```

**Expected:** Same response format with token and user object.

#### Get Current User

```bash
curl http://localhost:8000/api/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected:** User object with all profile fields.

#### Logout

```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected:** `{"message": "Logged out successfully"}`

### User Dashboard + Notifications (Auth Required)

#### Get User Engagement Dashboard

```bash
curl http://localhost:8000/api/creative/me/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

Expected: Counts for likes, bookmarks, comments, stories viewed, and unread notifications.

#### List Notifications

```bash
curl http://localhost:8000/api/creative/me/notifications \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### Mark Notification Read

```bash
curl -X POST http://localhost:8000/api/creative/me/notifications/NOTIFICATION_ID/read \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### Mark All Notifications Read

```bash
curl -X POST http://localhost:8000/api/creative/me/notifications/read-all \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Author Request Workflow (Auth Required)

#### Submit Author Request

```bash
curl -X POST http://localhost:8000/api/author/request \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "bio": "Short bio about your writing.",
    "sample_link": "https://example.com/writing-sample",
    "accepted_terms": true,
    "accepted_privacy": true,
    "accepted_ip_policy": true,
    "accepted_community_guidelines": true
  }'
```

#### Check Author Request Status

```bash
curl http://localhost:8000/api/author/request \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

Frontend status mapping:

- `data: null` => `none`
- `data.status` => `pending | approved | rejected`

### Public Reader Tests (No Auth Required)

#### 1. Browse Published Stories

```bash
curl http://localhost:8000/api/creative/stories
```

Expected: List of 2 published stories with author info and engagement counts.

#### 2. Get Story by Slug

```bash
curl http://localhost:8000/api/creative/stories/the-midnight-echo
```

Expected: Full story details + chapter list + categories/tags.

#### 3. Read Chapter

```bash
curl http://localhost:8000/api/creative/stories/the-midnight-echo/chapters/1
```

Expected: Chapter content + navigation (previous/next).

#### 4. Get Categories

```bash
curl http://localhost:8000/api/creative/categories
```

Expected: List of all categories.

#### 4.1 Get Tags (Sub-genres/Themes)

```bash
curl http://localhost:8000/api/creative/tags
```

Expected: List of tags.

#### 4.2 Filter Stories by Genre/Tag

```bash
curl "http://localhost:8000/api/creative/stories?category=fiction"
```

```bash
curl "http://localhost:8000/api/creative/stories?tag=adventure"
```

Expected: Story list filtered by category/tag slug.

#### 5. Get Trending Stories

```bash
curl http://localhost:8000/api/creative/trending?days=7
```

Expected: Empty or stories sorted by engagement score.

#### 6. Track View (No Auth Required)

```bash
curl -X POST http://localhost:8000/api/creative/stories/1/view \
  -H "Content-Type: application/json" \
  -d '{"chapter_id": 1}'
```

Expected: `{"success":true,"message":"View tracked"}`

#### 7. OG Metadata + Images (No Auth Required)

```bash
curl http://localhost:8000/api/creative/og/story/the-midnight-echo
```

```bash
curl http://localhost:8000/api/creative/og/story/the-midnight-echo/image --output og-story.png
```

Expected: PNG generated using story cover image when available.

```bash
curl http://localhost:8000/api/creative/og/story/the-midnight-echo/chapter/1
```

```bash
curl http://localhost:8000/api/creative/og/story/the-midnight-echo/chapter/1/image --output og-chapter.png
```

Expected: PNG generated using chapter featured image, fallback to story cover image.

---

## Authenticated User Tests (Login Required)

**First, login and get a token:**

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"reader@example.com","password":"password123"}'
```

Save the returned `token` and use it in the `Authorization: Bearer {token}` header below.

### 1. Like a Story

```bash
curl -X POST http://localhost:8000/api/likes \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"type":"story","id":1}'
```

**Expected:** `{"success":true,"message":"Story liked"}`

### 2. Bookmark a Story

```bash
curl -X POST http://localhost:8000/api/bookmarks \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"story_id":1}'
```

**Expected:** `{"success":true,"message":"Story bookmarked"}`

### 3. Post a Comment

```bash
curl -X POST http://localhost:8000/api/comments \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "chapter",
    "id": 1,
    "body": "Great chapter!",
    "parent_id": null
  }'
```

**Expected Response (201):**

```json
{
    "success": true,
    "data": {
        "id": 15,
        "user_id": 3,
        "body": "Great chapter!",
        "status": "visible"
    }
}
```

Save the comment `id` for edit/delete tests below.

### 4. Edit Your Own Comment

```bash
curl -X PATCH http://localhost:8000/api/comments/15 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "body": "Great chapter! Updated my thoughts."
  }'
```

**Expected (200):** `{"success":true,"data":{...}}`

**Note:** Only the comment owner can edit. Attempting to edit someone else's comment returns 403 Forbidden.

### 5. Delete Your Own Comment

```bash
curl -X DELETE http://localhost:8000/api/comments/15 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected (200):** `{"success":true,"message":"Comment deleted"}`

**Authorization:**

- ✅ Comment owner can delete
- ✅ Admin/moderator/editor can delete any comment
- ❌ Other users get 403 Forbidden

### 6. Save Reading Progress

```bash
curl -X POST http://localhost:8000/api/reading-progress \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "story_id": 1,
    "chapter_id": 1,
    "progress": 75
  }'
```

### 7. Upload Media

```bash
curl -X POST http://localhost:8000/api/media/upload \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -F "file=@/path/to/image.jpg" \
  -F "alt_text=Cover image"

# Compatible file field aliases: image, cover_image, coverImage, featured_image, featuredImage
```

**Response includes:**

```json
{
    "success": true,
    "data": {
        "media_id": 1,
        "url": "/storage/creative/uploads/...",
        "thumbnail_url": "/storage/creative/uploads/thumbs/..."
    }
}
```

### 7b. Check Effective Upload Limits

```bash
curl http://localhost:8000/api/media/upload-limits \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

Expected: JSON containing PHP limits (`upload_max_filesize`, `post_max_size`), app limit, and effective max.

### 7c. Check Media URL/Path Health

```bash
# By media ID
curl "http://localhost:8000/api/media/health?media_id=10" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# By path or URL
curl "http://localhost:8000/api/media/health?path=/storage/creative/uploads/example.jpg" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

Expected: `exists_on_disk`, filesystem path, generated URLs, and symlink/env diagnostics.

---

## Author Tests (Requires Author Role)

**First, login as an author:**

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"author@example.com","password":"password"}'
```

### 0. Author Dashboard

```bash
curl http://localhost:8000/api/author/dashboard \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN"
```

### 0.1 List Your Stories

```bash
curl http://localhost:8000/api/author/stories \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN"
```

### 1. Create a New Story

```bash
curl -X POST http://localhost:8000/api/author/stories \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My New Adventure",
    "summary": "An exciting journey begins",
    "description": "Full description here",
    "language": "en",
    "visibility": "public",
    "cover_image_id": null
  }'
```

**Expected:** Story object with `status: "draft"`

### 2. Add a Chapter to Your Story

```bash
curl -X POST http://localhost:8000/api/author/stories/YOUR_STORY_ID/chapters \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Chapter 1: The Beginning",
    "content_html": "<p>Once upon a time...</p>",
    "chapter_number": 1,
    "is_premium": false
  }'
```

**Expected:** Chapter object with auto-calculated `word_count` and `read_time_minutes`

### 3. Submit Story for Review

```bash
curl -X POST http://localhost:8000/api/author/stories/YOUR_STORY_ID/submit \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN"
```

**Expected:** Story status changes to `"pending"`

### 3.1 Author Preview Pending Story by Slug

```bash
curl http://localhost:8000/api/creative/stories/YOUR_PENDING_STORY_SLUG \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN"
```

Expected: `200` for story owner/admin/editor, even when status is pending/draft.

Without token (or non-owner non-admin), expected `404` for non-published story.

### 3.2 Author Preview Pending Chapter by Slug + Number

```bash
curl http://localhost:8000/api/creative/stories/YOUR_PENDING_STORY_SLUG/chapters/1 \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN"
```

Expected: `200` for owner/admin/editor preview access.

### 4. Delete Own Story

```bash
curl -X DELETE http://localhost:8000/api/author/stories/YOUR_STORY_ID \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN"
```

Expected: `200` with `success: true` and deletion message.

---

## Admin Tests (Author Requests + User Management)

### 1. List Author Requests

```bash
curl http://localhost:8000/api/admin/creative/author-requests \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 2. Approve or Reject Author Request

```bash
curl -X PATCH http://localhost:8000/api/admin/creative/author-requests/REQUEST_ID \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"approved","admin_notes":"Welcome aboard!"}'
```

Expected:

- Request status becomes `approved` (or `rejected` when rejected)
- `reviewed_at` is set
- Approved user's role is changed to `author`

### 2.1 Verify Role Changed Immediately

```bash
curl http://localhost:8000/api/user \
  -H "Authorization: Bearer APPROVED_USER_TOKEN"
```

Expected: user payload has `role: "author"`.

### 2.2 Verify Author Route Access (Approved User)

```bash
curl http://localhost:8000/api/author/dashboard \
  -H "Authorization: Bearer APPROVED_USER_TOKEN"
```

Expected: `200` success.

### 2.3 Verify Non-Author Gets 403

```bash
curl http://localhost:8000/api/author/dashboard \
  -H "Authorization: Bearer NORMAL_READER_TOKEN"
```

Expected: `403 Forbidden`.

### 3. Suspend or Ban User

```bash
curl -X PATCH http://localhost:8000/api/admin/creative/users/USER_ID \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"suspended"}'
```

### 4. Delete User Account

```bash
curl -X DELETE http://localhost:8000/api/admin/creative/users/USER_ID \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 5. List Unpublished Stories for Republish

```bash
curl "http://localhost:8000/api/admin/creative/stories?status=draft&page=1&per_page=20" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

You can also use `status=pending`, `status=archived`, or `status=all`.

### 5.1 Story Status Summary Badges

```bash
curl http://localhost:8000/api/admin/creative/stories/status-summary \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

Expected: counts for `all`, `draft`, `pending`, `published`, `archived`, `unpublished_total`.

### 5.2 List with Search + Sort

```bash
curl "http://localhost:8000/api/admin/creative/stories?status=all&search=midnight&sort=published_at&direction=desc&page=1&per_page=20" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 6. Open Story Detail from Admin Portal

```bash
curl http://localhost:8000/api/admin/creative/stories/STORY_ID \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

Expected: story detail returned even when status is not published.

### 6.1 Open Story by Slug from Admin Portal

```bash
curl http://localhost:8000/api/admin/creative/stories/by-slug/STORY_SLUG \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 7. Republish Unpublished Story

```bash
curl -X POST http://localhost:8000/api/admin/creative/stories/STORY_ID/publish \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

Expected: story status becomes `published` and public story route can resolve again.

---

## Test Error Handling

### 1. Database Error (Production Mode)

Set in `.env`:

```
APP_DEBUG=false
```

Then try:

```bash
curl http://localhost:8000/api/creative/trending
```

**Expected (if error occurs):**

```json
{
    "success": false,
    "message": "A database error occurred"
}
```

**No SQL exposed!**

### 2. Unauthenticated Request

```bash
curl -X POST http://localhost:8000/api/media/upload \
  -F "file=@image.jpg"
```

**Expected:**

```json
{
    "message": "Unauthenticated."
}
```

### 2.1 Missing Consent Flags on Author Request

```bash
curl -X POST http://localhost:8000/api/author/request \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "bio": "I write stories",
    "sample_link": "https://example.com/sample",
    "accepted_terms": false,
    "accepted_privacy": true,
    "accepted_ip_policy": true,
    "accepted_community_guidelines": true
  }'
```

Expected: `422` with validation errors for consent fields.

### 3. Invalid Story Slug

```bash
curl http://localhost:8000/api/creative/stories/nonexistent-story
```

**Expected (with custom handler):**

```json
{
    "success": false,
    "message": "Resource not found"
}
```

---

## Admin Tests

Login with an admin account (you'll need to create one or change author role to 'admin'):

```sql
UPDATE users SET role = 'admin' WHERE email = 'author@example.com';
```

Then test admin endpoints:

### 1. Admin Dashboard

```bash
curl http://localhost:8000/api/admin/creative/dashboard \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 2. Publish a Story

```bash
curl -X POST http://localhost:8000/api/admin/creative/stories/1/publish \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

### 3. Update User Role

```bash
curl -X PATCH http://localhost:8000/api/admin/creative/users/2 \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role":"editor"}'
```

---

## Verification Checklist

- [ ] Public stories endpoint returns data
- [ ] Story detail shows chapters
- [ ] Chapter reader works
- [ ] Categories/tags endpoints return data
- [ ] Login works and returns token
- [ ] Protected endpoints require auth
- [ ] Media upload creates file + thumbnail
- [ ] Likes/bookmarks save correctly
- [ ] Admin dashboard accessible to admins only
- [ ] Error responses don't leak SQL (when APP_DEBUG=false)

---

## Troubleshooting

### "Table not found" errors

Run migrations:

```bash
php artisan migrate
```

### Empty data responses

Seed the database:

```bash
php artisan db:seed --class=CreativeSeeder
```

---

## DOCX Import Tests (Implemented)

### 1. Import DOCX (Sync)

```bash
curl -X POST http://localhost:8000/api/author/stories/import-docx \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN" \
  -F "file=@/path/to/story.docx" \
  -F "import_mode=split_by_headings" \
  -F "category_ids[]=1" \
  -F "tag_ids[]=2"
```

Expected: `200`, created draft story + draft chapters + warnings + `import_reference`.

### 2. Check Import Job Status

```bash
curl http://localhost:8000/api/author/stories/import-docx/jobs/IMPORT_REFERENCE \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN"
```

Expected: `200`, with status `processing|completed|failed` and related story info.

### 3. Validation Error (Wrong File Type)

```bash
curl -X POST http://localhost:8000/api/author/stories/import-docx \
  -H "Authorization: Bearer YOUR_AUTHOR_TOKEN" \
  -F "file=@/path/to/story.pdf"
```

Expected: `422` with `errors.file`.

### 4. Forbidden for Non-Author Role

```bash
curl -X POST http://localhost:8000/api/author/stories/import-docx \
  -H "Authorization: Bearer NORMAL_READER_TOKEN" \
  -F "file=@/path/to/story.docx"
```

Expected: `403 Forbidden`.

### "Unauthenticated" on protected routes

Check token is in header:

```
Authorization: Bearer YOUR_TOKEN
```

### 500 errors with no message

Check logs:

```bash
tail -f storage/logs/laravel.log
```

---

**Status**: ✅ All endpoints ready for frontend integration
