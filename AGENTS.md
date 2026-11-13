# AGENTS.md

LnBlog is a file-based (no database) PHP weblog. All blog data lives in text, INI, or XML
files under `userdata/` and per-blog directories. One install can host multiple blogs.
PHP >= 7.4. See also `CLAUDE.md` for a fuller architecture write-up.

## Commands

```bash
composer install --dev && npm install    # install deps (both required)

vendor/bin/phing checks                  # CI gate: composer validate, npm audit,
                                         #   php -l, phpcs, phpstan, eslint themes
vendor/bin/phpunit                       # full test suite (tests/ via phpunit.xml)
vendor/bin/phpunit tests/unit/BlogEntryTest.php            # one file
vendor/bin/phpunit --filter testMethodName tests/unit/...  # one method
vendor/bin/phing client-local            # build third-party JS/CSS into third-party/
vendor/bin/phing devserver               # dev server; serves the PARENT dir, so URL is
                                         #   http://localhost:4080/lnblog/
php cli.php --upgrade                    # upgrade blog data format
```

Re-run both installs after pulling changes if `composer.lock`, `package.json`, or
`build.xml` changed. `third-party/` is gitignored build output; pages reference it, so
a fresh clone needs `phing client-local` before rendering correctly.

## Architecture

- **Entry points**: `pages/showblog.php` (public → `WebPages`), `index.php` (admin →
  `AdminPages`), `cli.php --cron` (→ `TaskManager`), `xmlrpc.php` (MetaWeblog API).
  `lib/BasePages.php` is the routing base; pages dispatch via action maps and render
  `lib/PHPTemplate.php` templates.
- **Storage model (most important)**: no DB. An entry is a directory at
  `{blog}/entries/YYYY/MM/DD/{d_Hi}/entry.xml`, with reply subdirectories inside the entry
  dir. Articles live under `{blog}/content/`. All file I/O goes through repositories in
  `lib/Storage/` and the `lib/FS.php` interface (`NativeFS` = disk, `FTPFS` = legacy FTP).
  Never hardcode direct file paths in new code.
- **Mixed class styles**: new code is PSR-4 (`LnBlog\` → `lib/`, `LnBlog\Plugins\` →
  `lib/Plugins` + `plugins/`). Much core is still legacy global-namespace classes
  (`Blog`, `AdminPages`, `WebPages`, `Path`, `System`, `Publisher`) loaded via a custom
  autoloader (`get_class_autoload()` in `blogconfig.php`). Don't namespace or rename those —
  they're referenced as globals everywhere.
- **Config**: `blogconfig.php` bootstraps autoloaders and constants; `lib/SystemConfig.php`
  loads `pathconfig.php` (see gotchas). Many behaviors are tunable via constants defined
  in `blogconfig.php`.
- **Plugins**: extend `lib/Plugin.php`; declare a `member_list` array for the auto-generated
  config UI and hook lifecycle events (`OnInit`, `OnInsert`, `OnUpdate`, `OnDelete`, …) via
  `lib/EventRegister.php`. Configs are stored in `userdata/{blog}/plugins.xml`.

## Gotchas

- **`pathconfig.php` is gitignored but required.** It defines `INSTALL_ROOT`,
  `USER_DATA_PATH`, and the blog registry. Every entry point and the test bootstrap
  (`tests/bootstrap.php` → `blogconfig.php`) depend on it. A fresh clone cannot run or test
  without creating one locally; never commit it.
- **Templates**: themes live in `themes/{name}/templates/`. Override precedence is
  blog-level → userdata → install root → default theme. Use the i18n helpers `_()`,
  `p_()`, `pf_()`, `spf_()` for user-facing strings; regenerate `po/messages.pot` with
  `vendor/bin/phing messages`.
- **Tests**: unit tests extend `LnBlog\Tests\LnBlogBaseTestCase`, which wraps Prophecy.
  phpunit.xml warns "Prophecy does not play well with the strict tests" — don't tighten
  strictness flags. Integration tests create/delete `test_temp/` and `feeds/` dirs in the
  repo root during run.
- **Static analysis**: PHPStan level 5 (`phpstan.neon`). PHPCS uses the Zend standard with
  exclusions (line length, variable naming). Frontend JS/CSS is linted via eslint on the
  `themes/` dir.
