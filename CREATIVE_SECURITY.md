# Creative API Security & Deployment Checklist

## Database Security

✅ **Migrations Complete**

- All creative module tables created
- User profile/status fields added
- Foreign keys enforced with cascades

✅ **Error Handling – Production Ready**

- Custom exception handler in `app/Exceptions/Handler.php`
- Database errors sanitized (never expose SQL/schema in production)
- Query exceptions return generic "database error" when `APP_DEBUG=false`

## Environment Configuration

### Required `.env` Settings for Production

```env
APP_DEBUG=false
APP_ENV=production
LOG_LEVEL=error
```

**Critical**: With `APP_DEBUG=false`, the exception handler will:

- Hide SQL statements from error responses
- Mask table/column names
- Return generic error messages to API consumers

### Rate Limiting (Already Configured)

- Login/register: `10 requests/minute`
- Comments/reports: `15 requests/minute`
- Likes: `20 requests/minute`
- Media upload: `10 requests/minute`
- View tracking: `120 requests/minute`

## Security Features Implemented

### 1. Input Sanitization

- Chapter HTML sanitized on save AND on read (defense in depth)
- Allowlist: `<p><br><h1><h2><h3><h4><ul><ol><li><strong><em><a><img><blockquote>`
- Scripts, iframes, event handlers stripped

### 2. Authentication & Authorization

- Sanctum token-based auth
- Role-based middleware (`admin`, `role:author,editor,...`)
- Ownership checks in controllers (author can only edit own stories)
- User status checks (banned/suspended users blocked)

### 3. Audit Logging

- All publish/unpublish actions logged
- User role/status changes logged
- Moderation actions logged
- Logs stored in `audit_logs` table with IP + user agent

### 4. Upload Security

- MIME type validation: `jpg|jpeg|png|webp` only
- File size limit: `10MB`
- Server-side thumbnail generation
- Files stored outside public doc root by default (`storage/app/creative/uploads`)

### 5. Database Security

- Prepared statements (Laravel Eloquent default)
- Foreign key constraints enforced
- Unique constraints on critical fields (slugs, user-story bookmarks, etc.)

## Attack Surface Mitigation

| Attack Vector       | Mitigation                                   |
| ------------------- | -------------------------------------------- |
| XSS (stored)        | HTML sanitization on chapter content         |
| SQL Injection       | Eloquent ORM + prepared statements           |
| Info Disclosure     | Exception handler masks errors in production |
| Brute Force         | Rate limiting on auth/engagement endpoints   |
| Unauthorized Access | Role middleware + ownership checks           |
| CSRF                | Disabled for API (Bearer token auth instead) |
| Mass Assignment     | `$fillable` arrays strictly defined          |

## Pre-Deployment Commands

```bash
# 1. Run migrations
php artisan migrate --force

# 2. Clear/cache config for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Set proper storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 4. Generate app key if not set
php artisan key:generate
```

## Monitoring Recommendations

1. **Log SQL Errors** – Check `storage/logs/laravel.log` for query exceptions
2. **Monitor Rate Limits** – Track `429` responses to detect abuse
3. **Audit Log Review** – Periodically check `audit_logs` for anomalies
4. **Failed Login Tracking** – Monitor auth failures for brute force attempts

## Known Configuration Note

- `spatie/laravel-permission` package install failed in dev environment
- Role checks currently use `users.role` column + custom middleware
- Endpoints for Spatie roles/permissions scaffolded but return `501` until package is installed
- Migration to Spatie can be done later without breaking existing auth

## API Response Format (Error Cases)

### Production (APP_DEBUG=false)

```json
{
    "success": false,
    "message": "A database error occurred"
}
```

### Development (APP_DEBUG=true)

```json
{
    "success": false,
    "message": "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'xyz'..."
}
```

## Emergency Response

If you detect an attacker exploiting info disclosure:

1. Set `APP_DEBUG=false` immediately
2. Clear config cache: `php artisan config:clear`
3. Review `storage/logs/laravel.log` for attack pattern
4. Check `audit_logs` and `story_views` tables for suspicious IPs
5. Consider IP blocking at web server level (Nginx/Apache)

---

**Status**: ✅ All security measures active and tested
**Last Updated**: 2026-02-21
