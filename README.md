# julianfalk.dev notes

## Adding blog posts

- **Via SQLite CLI (fastest)**  
  ```
  sqlite3 julianfalk.dev.db "INSERT INTO blog_posts (title, content, created_at) VALUES ('Your Title', 'Body text here', '2025-03-01 10:00:00');"
  ```
  - Omit `created_at` to let SQLite default to now.
  - Add a hero image by setting `image_url` to a public path, e.g. `/assets/blog/your-slug/hero.jpg`.
  - Titles are slugified for URLs; keep them unique enough to avoid confusing slugs.
  - Content supports inline images with markdown syntax: `![alt text](/assets/blog/your-slug/image.jpg)`.

- **Via a tiny PHP helper (calls the existing function)**  
  Create a one-off script, then run `php script.php`:
  ```php
  <?php
  require 'blog.php';
  addBlogPost('Your Title', 'Body text here');
  ```

Notes:
- Everything is stored in `julianfalk.dev.db` (SQLite).
- There is no admin UI enabled, so DB/CLI is the current path for publishing.***
