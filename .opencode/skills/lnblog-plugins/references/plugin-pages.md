# Plugin pages (`outputPage()` routing)

A "plugin page" lets a plugin own a URL and perform arbitrary request
handling (forms, actions, exports) instead of just emitting output through
events.

## Real URL scheme

```
index.php?plugin=ClassName&do=subroute[&other params]
```

The `do` query parameter (`Plugin::ROUTING_PARAMETER`) selects a sub-route;
everything else is plugin-defined (e.g. `entry`, `blog`).

**Note:** the docblock on `Plugin::outputPage()` says to use
`?action=plugin&plugin=...` — that is **stale**. `BasePages::handleRequestRouting()`
never requires an `action` value for plugin routing (a fix to the docblock is
planned). `UrlResolver::getRoute('plugin', $params)` also builds plain
`?plugin=...&...` URLs; use it for link generation.

## Routing mechanics (`lib/BasePages.php`, `handleRequestRouting()`)

The router checks, in order:

1. The action map (`getActionMap()`) — page actions win over plugin URLs.
2. `$_GET['script']` and `$_GET['style']` asset dumping.
3. `$_GET['plugin']` — the plugin-page branch:
   - Defines `PLUGIN_DO_OUTPUT` (`true`).
   - Looks for `{path}/plugins/{ClassName}.php` in `BLOG_ROOT` (if defined),
     `USER_DATA_PATH`, `INSTALL_ROOT` — same precedence as loading.
   - Reuses the instance from `PluginManager::getInstanceForFile($plugin)`
     when the plugin was already loaded this request; otherwise `require`s
     the file and invokes its returned loader closure.
   - Calls `$instance->outputPage($this, $_GET['do'] ?? '')`.

This is why the canonical `getLoader()` style matters: only loader-style
plugins have a stored instance to reuse; legacy bottom-of-file plugins get a
second instance constructed for the request (see
`references/legacy-and-roadmap.md`).

⚠️ The `preg_replace('[^A-Za-z0-9_\-]', '', $_GET['plugin'])` "sanitization"
in this branch is broken (no regex delimiters → effectively a no-op). It is a
known bug with a planned fix; **do not copy it** as a pattern.

## The `outputPage()` contract

```php
public function outputPage(BasePages $web_page, string $action = ''): void;
```

- `$web_page` — the invoking pages object; use it for access checks and to
  render (`$web_page->getPage()`).
- `$action` — the `do` parameter. Unknown/missing routes should end in an
  error, conventionally `$web_page->getPage()->error(400)`.
- Plugins read their own request parameters (`GET`/`POST` helpers) and are
  responsible for all output.

### Worked example: `plugins/rss_management.php`

```php
public function outputPage(BasePages $web_page, string $action = ''): void {
    switch ($action) {
        case 'purgeblog':
            $blog = $this->getBlog();
            $this->checkPageAccess($web_page, $blog);
            $this->purgeBlogFeeds($blog);
            break;
        // ... other sub-routes ...
        default:
            $web_page->getPage()->error(400);
    }
}
```

Called as `index.php?plugin=rss_management&do=purgeblog`.

### Access control pattern

Follow the bundled pattern: resolve the target object (blog or entry), then
verify the current user may modify it before acting:

```php
$this->checkPageAccess($web_page, $blog);   // rss_management helper
```

which wraps the standard `System::instance()->canModify($obj, $user)` +
login checks used across `lib/`. Never perform state-changing actions
(deleting feeds, purging data) without it.

## Entry-point context

Plugin pages are served by the admin entry point `index.php` (the
`AdminPages`/`WebPages` routing base). `outputPage()` is *not* called for
plain page loads — a plugin that also does event-driven output does that
through handlers registered in its constructor.
