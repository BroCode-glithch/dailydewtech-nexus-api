# API Testing Guide

Quick reference for testing your improved API endpoints.

## Test Environment

Base URL: `http://localhost/api` (adjust for your setup)

---

## 1. Test Public Endpoints (No Authentication Required)

### Get Published Posts

```bash
# PowerShell
Invoke-RestMethod -Uri "http://localhost/api/public/posts?per_page=5" -Method Get
```

### Get Published Projects

```bash
Invoke-RestMethod -Uri "http://localhost/api/public/projects" -Method Get
```

### Get Random Quote

```bash
Invoke-RestMethod -Uri "http://localhost/api/public/quotes/random" -Method Get
```

### Test Contact Form (Rate Limited: 5/min)

```bash
$body = @{
    name = "Test User"
    email = "test@example.com"
    subject = "Test Message"
    message = "This is a test message"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/api/contact" `
    -Method Post `
    -Body $body `
    -ContentType "application/json"
```

---

## 2. Test Authentication

### Register New User (Rate Limited: 10/min)

```bash
$body = @{
    name = "John Doe"
    email = "john@example.com"
    password = "password123"
    password_confirmation = "password123"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://localhost/api/register" `
    -Method Post `
    -Body $body `
    -ContentType "application/json"

# Save token for later use
$token = $response.token
```

### Login (Rate Limited: 10/min)

```bash
$body = @{
    email = "info@dailydewtech.com.ng"
    password = "password"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://localhost/api/login" `
    -Method Post `
    -Body $body `
    -ContentType "application/json"

$token = $response.token
echo "Token: $token"
```

---

## 3. Test Admin Authentication

### Step 1: Request Login Code

```bash
$body = @{
    email = "info@dailydewtech.com.ng"
    password = "password"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/api/admin/login/request-code" `
    -Method Post `
    -Body $body `
    -ContentType "application/json"

# Check your email for the code
```

### Step 2: Verify Code

```bash
$body = @{
    email = "info@dailydewtech.com.ng"
    code = "123456"  # Use the code from email
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://localhost/api/admin/login/verify-code" `
    -Method Post `
    -Body $body `
    -ContentType "application/json"

$adminToken = $response.token
```

---

## 4. Test Protected Endpoints

### Get Current User

```bash
$headers = @{
    Authorization = "Bearer $token"
}

Invoke-RestMethod -Uri "http://localhost/api/user" `
    -Method Get `
    -Headers $headers
```

### Get Dashboard Stats

```bash
$headers = @{
    Authorization = "Bearer $adminToken"
}

Invoke-RestMethod -Uri "http://localhost/api/admin/stats" `
    -Method Get `
    -Headers $headers
```

---

## 5. Test Post Management

### Create New Post (Protected)

```bash
$headers = @{
    Authorization = "Bearer $adminToken"
}

$body = @{
    title = "My Test Post"
    content = "<p>This is the content of my test post. It's a rich text content.</p>"
    cover_image = "https://images.unsplash.com/photo-1544383835-bda2bc66a55d?w=800"
    tags = @("test", "api")
    status = "published"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://localhost/api/admin/posts" `
    -Method Post `
    -Headers $headers `
    -Body $body `
    -ContentType "application/json"

$postId = $response.data.id
echo "Created post ID: $postId"
```

### Get All Posts (Admin - can see drafts)

```bash
$headers = @{
    Authorization = "Bearer $adminToken"
}

Invoke-RestMethod -Uri "http://localhost/api/admin/posts?status=all&per_page=10" `
    -Method Get `
    -Headers $headers
```

### Update Post

```bash
$headers = @{
    Authorization = "Bearer $adminToken"
}

$body = @{
    title = "Updated Title"
    status = "draft"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/api/admin/posts/$postId" `
    -Method Put `
    -Headers $headers `
    -Body $body `
    -ContentType "application/json"
```

### Publish/Unpublish Post

```bash
# Publish
Invoke-RestMethod -Uri "http://localhost/api/admin/posts/$postId/publish" `
    -Method Post `
    -Headers $headers

# Unpublish
Invoke-RestMethod -Uri "http://localhost/api/admin/posts/$postId/unpublish" `
    -Method Post `
    -Headers $headers
```

---

## 6. Test Project Management

### Create Project

```bash
$headers = @{
    Authorization = "Bearer $adminToken"
}

$body = @{
    title = "Test Project"
    description = "A test project description"
    category = "Web Development"
    technologies = @("PHP", "Laravel", "React")
    link = "https://example.com"
    status = "published"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://localhost/api/admin/projects" `
    -Method Post `
    -Headers $headers `
    -Body $body `
    -ContentType "application/json"

$projectId = $response.data.id
```

### Get Related Projects

```bash
Invoke-RestMethod -Uri "http://localhost/api/public/projects/$projectId/related" `
    -Method Get
```

---

## 7. Test Message Management

### List Messages

```bash
$headers = @{
    Authorization = "Bearer $adminToken"
}

Invoke-RestMethod -Uri "http://localhost/api/admin/messages?page=1&status=unread" `
    -Method Get `
    -Headers $headers
```

### Mark Message as Read

```bash
$body = @{
    status = "read"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/api/admin/messages/1" `
    -Method Put `
    -Headers $headers `
    -Body $body `
    -ContentType "application/json"
```

### Soft Delete Message

```bash
Invoke-RestMethod -Uri "http://localhost/api/admin/messages/1" `
    -Method Delete `
    -Headers $headers
```

### Get Trashed Messages

```bash
Invoke-RestMethod -Uri "http://localhost/api/admin/messages/trashed" `
    -Method Get `
    -Headers $headers
```

### Restore Message

```bash
Invoke-RestMethod -Uri "http://localhost/api/admin/messages/1/restore" `
    -Method Post `
    -Headers $headers
```

### Permanently Delete

```bash
Invoke-RestMethod -Uri "http://localhost/api/admin/messages/1/force" `
    -Method Delete `
    -Headers $headers
```

---

## 8. Test Rate Limiting

### Test Contact Form Rate Limit (5/min)

```bash
# Run this 6 times quickly - the 6th should fail
1..6 | ForEach-Object {
    $body = @{
        name = "Test $_"
        email = "test$_@example.com"
        subject = "Test"
        message = "Test message $_"
    } | ConvertTo-Json

    try {
        Invoke-RestMethod -Uri "http://localhost/api/contact" `
            -Method Post `
            -Body $body `
            -ContentType "application/json"
        echo "Request $_ succeeded"
    } catch {
        echo "Request $_ failed: $($_.Exception.Message)"
    }

    Start-Sleep -Milliseconds 500
}
```

---

## 9. Test Search & Filtering

### Search Posts

```bash
Invoke-RestMethod -Uri "http://localhost/api/public/posts?search=web&per_page=5" `
    -Method Get
```

### Filter by Tag

```bash
Invoke-RestMethod -Uri "http://localhost/api/public/posts?tag=tech" `
    -Method Get
```

### Filter Projects by Category

```bash
Invoke-RestMethod -Uri "http://localhost/api/public/projects?category=Web Development" `
    -Method Get
```

---

## 10. Test Error Handling

### Test 404 Error

```bash
try {
    Invoke-RestMethod -Uri "http://localhost/api/public/posts/99999" -Method Get
} catch {
    $_.Exception.Response.StatusCode
}
```

### Test 401 Unauthorized

```bash
# Try accessing protected endpoint without token
try {
    Invoke-RestMethod -Uri "http://localhost/api/admin/stats" -Method Get
} catch {
    echo "Expected 401 error"
}
```

### Test Validation Error

```bash
# Missing required fields
$body = @{
    title = "Test"
    # Missing required 'content' field
} | ConvertTo-Json

try {
    Invoke-RestMethod -Uri "http://localhost/api/admin/posts" `
        -Method Post `
        -Headers @{ Authorization = "Bearer $adminToken" } `
        -Body $body `
        -ContentType "application/json"
} catch {
    echo "Expected 422 validation error"
}
```

---

## Expected Results

### Success Response Format

```json
{
  "success": true,
  "message": "Optional message",
  "data": {...}
}
```

### Error Response Format

```json
{
  "success": false,
  "message": "Error message",
  "errors": {...}
}
```

### Pagination Response

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 50,
    "per_page": 15,
    ...
  }
}
```

---

## Troubleshooting

### If routes don't work:

```bash
# Clear all caches
& 'C:\wamp64\bin\php\php8.3.14\php.exe' artisan route:clear
& 'C:\wamp64\bin\php\php8.3.14\php.exe' artisan config:clear
& 'C:\wamp64\bin\php\php8.3.14\php.exe' artisan cache:clear
```

### If authentication fails:

```bash
# Check if user exists
& 'C:\wamp64\bin\php\php8.3.14\php.exe' artisan tinker --execute "App\Models\User::where('email', 'info@dailydewtech.com.ng')->first()"
```

### Check route list:

```bash
& 'C:\wamp64\bin\php\php8.3.14\php.exe' artisan route:list
```

---

## Notes

1. **Excerpt Auto-Generation**: If you don't provide an `excerpt` when creating a post, it will be automatically generated from the content (first 200 characters, HTML stripped).

2. **Slug Auto-Generation**: Slugs are automatically generated from titles with a unique ID appended to prevent duplicates.

3. **Rate Limiting**: Be aware of rate limits when testing:
    - Authentication: 10/minute
    - Contact form: 5/minute
    - Admin auth: 5/minute

4. **Default Filters**: Public endpoints default to showing only published content. Use `status=all` in admin routes to see all content.

5. **Pagination**: Default is 15 items per page. Adjust with `?per_page=N` parameter.
