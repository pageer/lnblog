# Testing plugins

Bundled plugins have unit tests in `tests/unit/Plugins/` (e.g.
`AuthorBioTest.php`, `RssManagementTest.php`, `BotBlockTest.php`). Tests use
PHPUnit with the project base case `LnBlog\Tests\LnBlogBaseTestCase`, which
wraps Prophecy — see `AGENTS.md` for the suite's caveats (don't tighten
strictness flags).

Run tests:

```sh
vendor/bin/phpunit tests/unit/Plugins/AuthorBioTest.php
vendor/bin/phpunit --filter testMethodName tests/unit/Plugins/RssManagementTest.php
```

## Conventions

### Constructor injection ("cheap DI")

Plugin constructors accept nullable dependencies and default to the real
service, so tests can pass doubles:

```php
// plugins/rss_management.php
public function __construct(
    ?FS $fs = null,
    ?ExporterFactory $exporterFactory = null,
    ?UrlResolver $urlResolver = null,
    ?System $system = null,
    ?LoggerInterface $logger = null
) {
    $this->fs = $fs ?: NewFS();
    // ...
}
```

Heavy collaborators that need per-test stubbing are exposed as public test
doubles, e.g. `public ?Blog $test_blog = null;` consumed by a getter like
`getBlog()` (which falls back to `new Blog()` in production).

### Coverage exclusions

- `getLoader()` is marked `@codeCoverageIgnore`.
- The trailing `return MyWidget::getLoader();` is wrapped in
  `// @codeCoverageIgnoreStart` / `// @codeCoverageIgnoreEnd` because include
  side effects can't be meaningfully unit-tested.

### Global context tests

Plugins that branch on `Plugin::globalContext()` (e.g. `bot_block.php`)
switch the static context in the test and restore it afterwards:

```php
// tests/unit/Plugins/BotBlockTest.php
BotBlock::globalContext(BotBlock::CONTEXT_CLI);
// ... assertions ...
BotBlock::globalContext(BotBlock::CONTEXT_WEB);  // restore
```

Note `CONTEXT_WEB` is the production default; `CONTEXT_IMPORT` is set
temporarily by `BlogImportForm`, and `CONTEXT_CLI` is only ever set from
tests today.

## What to test

- `buildOutput()` / rendering methods with injected doubles (template
  processing, filtering, escaping).
- Option-driven behavior branches (checkboxes, selects).
- `outputPage()` sub-routes, including the `default: ...->error(400)` path.
- Event-handler methods' guard clauses (e.g. `ReplyNotifier::comment_notify`
  skipping owner replies).

## Handicaps to work around

- The static plugin registry and `EventRegister` singleton persist within a
  request; use `EventRegister::instance()->clearAll()` where isolation
  matters.
- `Plugin::__construct()` registers with `PluginManager::instance()` —
  instantiating a plugin in a test populates the global registry; harmless
  but worth knowing when asserting registry contents.
