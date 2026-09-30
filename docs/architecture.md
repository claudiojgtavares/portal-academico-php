# Arquitetura / Architecture

```text
Browser
  -> public entry points (index.php, login.php, candidatura.php)
  -> actions/*.php (validation + authorization + transaction)
  -> includes/auth.php + helpers.php + portal_layout.php
  -> PDO/MySQL (prepared statements)
```

The seven role dashboards reuse a common layout. Writes go through action endpoints, are protected by CSRF tokens and are logged when the underlying module supports audit logs. Uploads are stored with random names and validated by extension, MIME type and size.

The public demo is intentionally local-only: GitHub Pages is static hosting and cannot run the PHP/MySQL runtime.
