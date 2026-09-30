# Security checklist

- [x] Database credentials come from environment variables.
- [x] Generic database error shown to visitors; details go to the server log.
- [x] Session cookie configured with `HttpOnly`, `SameSite=Lax` and HTTPS-aware `Secure`.
- [x] CSRF token generated per session and checked on POST actions.
- [x] Uploads use random server-side names, `0750` directories, size limits and MIME checks.
- [x] Existing uploads and database dumps with identifiable data are excluded from the public repository.
- [ ] Production deployment review: HTTPS, secret manager, backups, dependency audit and penetration test.
