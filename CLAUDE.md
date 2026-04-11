# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies
composer install --dev
npm install

# Run all quality checks (lint + phpstan + phpcs + eslint)
vendor/bin/phing checks

# Run tests
vendor/bin/phpunit

# Run a single test file
vendor/bin/phpunit tests/unit/BlogEntryTest.php

# Run a single test method
vendor/bin/phpunit --filter testMethodName tests/unit/BlogEntryTest.php

# Build third-party JS/CSS for local development
vendor/bin/phing client-local

# Local dev server
php -S localhost:4080
```

## Architecture

LnBlog is a **file-based blogging system** — no database. A single instance can support 
multiple blogs.  All blog data is stored in text, INI, or XML files. Global data is stored 
under the `userdata/` directory. Each blog has a dedicated directory, which normally lives
outside the main LnBlog project. Blog entries are stored in a directory hierarchy under the 
blog's folder, organized by year/month/day. Each entry has a dedicated directory, containing
a file for the entry content and metadata, and subdirectories for replies.
This is the most important thing to understand when working with storage code.

### Entry Points

- `pages/showblog.php` — public blog view → `WebPages` class
- `index.php` — admin UI → `AdminPages` class
- `cli.php --cron` — scheduled task runner → `TaskManager`
- `xmlrpc.php` — MetaWeblog API

### Layers

**Routing**: `lib/BasePages.php` (base), `lib/WebPages.php` (public), `lib/AdminPages.php` (admin). Each has an action map that dispatches requests to methods, which render templates via `PHPTemplate`.

**Domain Models**: `lib/Blog.php`, `lib/BlogEntry.php`, `lib/BlogComment.php`, `lib/User.php`. `BlogEntry` and `Article` both extend the abstract `lib/Entry.php`.

**Storage/Repository**: `lib/Storage/` — `EntryRepository`, `BlogRepository`, `UserRepository`, `ReplyRepository` abstract file I/O behind a consistent interface. The filesystem itself is abstracted through `lib/FS.php` (interface), `lib/NativeFS.php` (real disk), `lib/FTPFS.php` (legacy FTP).

**Plugin System**: `lib/Plugin.php` (abstract base) + `lib/PluginManager.php` (singleton registry). Plugins declare a `member_list` array for auto-generated config UI and hook into lifecycle events (`OnInit`, `OnInsert`, `OnUpdate`, `OnDelete`, etc.) via `lib/EventRegister.php`. Plugin configs are stored in `userdata/{blog}/plugins.xml`.

**Templates**: `lib/PHPTemplate.php` — simple PHP-based templates. Theme files live in `themes/{name}/templates/`. Override precedence: blog-level → user data → install root → default theme.

### Configuration

`pathconfig.php` (root) defines `INSTALL_ROOT`, `INSTALL_ROOT_URL`, `USER_DATA_PATH`, and the blog registry. `blogconfig.php` (root) bootstraps the autoloader and global constants. `lib/SystemConfig.php` is the runtime loader for these.

### Key Design Patterns

- **Singleton**: `Page`, `PluginManager`, `SystemConfig`, `System`
- **Repository**: all storage in `lib/Storage/`
- **Strategy**: exporters (`lib/Export/`), filesystems (`NativeFS`/`FTPFS`)
- **Event hooks**: plugins subscribe to named events via `EventRegister`

### Tests

Unit tests in `tests/unit/`, integration tests in `tests/integration/`. Test bootstrap is `tests/bootstrap.php`. PHPUnit config is `phpunit.xml`. Static analysis config is `phpstan.neon` (level 5); coding standards config is `phpcs.xml` (Zend-based).
