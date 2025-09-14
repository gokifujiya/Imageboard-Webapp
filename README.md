# Text-Snippet-Sharing-Service
# Text Snippet Sharing Service

A lightweight **Pastebin-like web application** built with **PHP 8 + MySQL**, allowing users to share plain text or code snippets with syntax highlighting, unique URLs, and optional expiration.

## 🚀 Features

- Paste text or code snippets easily without creating an account.
- **Unique shareable URLs** for each snippet:
  - `/s/{slug}` → formatted snippet view
  - `/raw/{slug}` → plain-text raw snippet
- **Syntax highlighting** for popular languages (PHP, JavaScript, Python, etc.).
- **Expiration options**:
  - 10 minutes
  - 1 hour
  - 1 day
  - Keep (no expiry)
- API support for automation (`/api/snippets`).
- Secure storage with **prepared statements** and **input validation**.
- Built-in migration system to manage database schema.

---
# Online-Image-Hosting-Service
