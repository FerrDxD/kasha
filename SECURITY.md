<div align="center">

# 🔐 Security Policy

**Last Updated: October 2026**

</div>

---

## 🛡️ Security Philosophy

KASHA handles sensitive financial data. Security is not an afterthought—it's foundational to every architectural decision. This document outlines our security measures, vulnerability reporting process, and best practices for contributors.

---

## 🎯 Security Measures

### Data Isolation

**Multi-Tenancy by Design**

- Every database table includes `user_id` for row-level isolation
- Global scopes automatically filter queries to the authenticated user
- Policies enforce authorization on every resource access
- Soft deletes prevent accidental data exposure

```php
// Example: Transaction model with user isolation
class Transaction extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('user', function ($query) {
            $query->where('user_id', auth()->id());
        });
    }
}
```

### Authentication & Authorization

- **Session-based auth** via Laravel Fortify/Breeze
- CSRF protection on all state-changing requests
- Password hashing with Laravel's bcrypt (Argon2ID)
- Two-factor authentication support (passkeys)
- Session invalidation on password change

### Data Protection

- **All financial amounts stored as `bigint`** (integer) - no floating-point precision issues
- SQL injection prevention via Eloquent ORM and parameterized queries
- XSS protection via Laravel's blade escaping and Vue's text interpolation
- Rate limiting on authentication endpoints
- Input validation on all user data

### API Security

- **Inertia.js** eliminates API surface - no REST/GraphQL endpoints to exploit
- Signed URLs for file downloads
- Signed cron job endpoints for scheduled tasks
- No direct database access from frontend

### Infrastructure Security

- **Supabase Row Level Security (RLS)** as defense-in-depth
- Vercel automatic HTTPS
- Environment variables for secrets (never committed)
- Database connection pooling via Supabase pooler (port 6543)
- Regular automated backups via Supabase

### File Upload Security

- MIME type validation for CSV uploads
- File size limits (configurable via `.env`)
- Virus scanning via Supabase Storage hooks
- Sanitization of CSV data before processing
- User-scoped storage paths

---

## 🔍 Vulnerability Reporting

### Reporting a Vulnerability

**Do NOT open a public issue.**

If you discover a security vulnerability, please disclose it responsibly:

1. **Email**: maulanaferdi0678@gmail.com
2. **Subject**: `[KASHA Security] - [Brief Description]`
3. **Include**:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if known)

### Response Timeline

| Time Frame | Action |
|------------|--------|
| Within 48 hours | Acknowledge receipt of report |
| Within 7 days | Investigation and assessment |
| Within 14 days | Fix deployment or mitigation plan |
| Within 30 days | Public disclosure (with credit) |

### What to Expect

- We will confirm receipt of your report within 48 hours
- We will provide regular updates on our progress
- We will credit you in the security advisory (if desired)
- We may offer a bug bounty for critical vulnerabilities

---

## 🚨 Security Best Practices for Contributors

### Never Commit Secrets

```bash
# ❌ NEVER commit
.env
.env.local
*.key
*.pem
oauth-credentials.json
```

### Use Environment Variables

```php
// ✅ Correct
$apiKey = env('STRIPE_API_KEY');

// ❌ Never hardcode
$apiKey = 'sk_live_1234567890';
```

### Validate All User Input

```php
// ✅ Always validate
$request->validate([
    'amount' => 'required|integer|min:0',
    'category_id' => 'required|uuid|exists:categories,id',
]);

// ❌ Never trust input directly
$transaction->amount = $request->amount;
```

### Use Parameterized Queries

```php
// ✅ Correct (Eloquent ORM)
$transactions = Transaction::where('amount', '>', 1000)->get();

// ❌ Never raw SQL with user input
DB::select("SELECT * FROM transactions WHERE amount > $amount");
```

### Sanitize File Uploads

```php
// ✅ Validate file type
$validated = $request->validate([
    'file' => 'required|mimes:csv,txt|max:10240', // 10MB max
]);

// ❌ Never accept arbitrary files
```

### Follow Least Privilege

- Request only the permissions you need
- Use scoped database queries
- Apply policies to all controller actions
- Never expose user data to other users

---

## 🔧 Security Checklist

Before submitting code, verify:

- [ ] No secrets committed (check `git diff`)
- [ ] All user input validated
- [ ] SQL queries use ORM or parameterized bindings
- [ ] File uploads validated and sanitized
- [ ] Authorization checks present (policies)
- [ ] CSRF tokens included in forms
- [ ] XSS escaping where appropriate
- [ ] Rate limiting on sensitive endpoints
- [ ] Error messages don't leak sensitive info
- [ ] Logging doesn't include secrets

---

## 📊 Known Limitations

### Current Trade-offs

1. **Vercel PHP Runtime**: Community-maintained, not official
   - Mitigation: Plan B migration path to Railway/Fly/VPS documented
   - Impact: Cold starts, function size limits

2. **No Queue Worker**: Vercel doesn't support persistent workers
   - Mitigation: Vercel Cron + chunked processing
   - Impact: Large CSV imports processed in chunks

3. **Session Driver**: Database-based (no Redis in free tier)
   - Mitigation: Upgrade to Upstash Redis for production
   - Impact: Slightly slower session access

### Future Improvements

- [ ] Implement audit logging for financial transactions
- [ ] Add device fingerprinting for suspicious activity detection
- [ ] Implement API rate limiting per user
- [ ] Add 2FA enforcement for sensitive operations
- [ ] Implement account locking after failed attempts

---

## 📚 Additional Resources

- [Laravel Security Documentation](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Supabase Security Best Practices](https://supabase.com/docs/guides/platform/security)
- [Vercel Security Features](https://vercel.com/docs/security)

---

## 🤝 Responsible Disclosure

We believe in responsible disclosure and will work with security researchers to:

- Acknowledge and validate reports
- Provide timely updates on remediation
- Credit researchers in security advisories
- Avoid legal action for responsible disclosure

**We will never pursue legal action against researchers who:**

- Follow this disclosure policy
- Report vulnerabilities responsibly
- Do not exploit or harm users
- Allow us reasonable time to respond

---

<div align="center">

**Security is a team effort. Thank you for helping keep KASHA safe.**

[⬆ Back to Top](#-security-policy)

</div>
