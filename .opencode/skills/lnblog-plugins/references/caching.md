# Output caching

`lib/Plugin.php` ships a small caching framework for plugins that render
sidebar widgets or other repeated output. The canonical consumer is
`plugins/sidebar_recent.php` (including its overrides).

## The pieces

| Method | Role |
| ------ | ---- |
| `cachepath($obj)` | Local path for this plugin's cache file. Default: `{blog}/cache/{CLASS}_output.cache` when `$obj` is a blog; `false` otherwise. Override for multiple/multi-object caches. |
| `buildOutput($obj)` | Generates the output string. **Plugins using the cache must override this** (base returns `''`). |
| `outputCache($obj, $suppress_login = true)` | Renders: live-echo or cache read/write, see below. |
| `invalidateCache($obj)` | Deletes the cache file. |
| `registerStandardInvalidators()` | Hooks `invalidateCache` onto `blogentry`/`article` `UpdateComplete`/`InsertComplete`/`DeleteComplete` and `blog` `UpdateComplete`. |

## `outputCache()` behavior (base implementation)

1. Always calls `$this->buildOutput($b)` first.
2. Returns early if the output is empty and `$this->allow_empty_output` is
   not set — an empty widget produces no markup and no cache file.
3. **Logged-in users always get live output** (echo of `buildOutput()`), as
   do plugins with `$this->enable_cache === false`. Rationale: never show
   stale/cached data to logged-in users, who may have just changed content or
   have permission-specific views. `$suppress_login` controls this check.
4. Otherwise (anonymous visitor, cache enabled): if the cache file is
   missing, `buildOutput()` output is written to `cachepath()` (creating the
   `cache/` directory if needed), then the file is `readfile()`d.

Note that in the base implementation `buildOutput()` runs even on a cache
hit (its result is discarded after the file is confirmed present) — the
cache saves only the `readfile`. `Recent` overrides `outputCache()` with its
own variant that builds output only to regenerate *missing* cache files.

## Overriding `cachepath()` for multiple caches

`Recent` keeps two variants (front-page "older entries" vs. other pages
"recent entries"), so it overrides both `cachepath()` and `invalidateCache()`
to manage `{blog}/cache/Recent_old_output.cache` and
`Recent_new_output.cache`. If you override `cachepath()`, remember to
override `invalidateCache()` too — the base one only deletes the single
default path.

Plugins caching per-entry or other non-blog paths can key `cachepath($obj)`
off the passed object.

## Invalidations

`registerStandardInvalidators()` is the standard bundle:

- `blogentry`: `UpdateComplete`, `InsertComplete`, `DeleteComplete`
- `article`: `UpdateComplete`, `InsertComplete`, `DeleteComplete`
- `blog`: `UpdateComplete`

Call it in the constructor (after `parent::__construct()`). Cache invalidation
also happens automatically when a plugin's config is saved
(`Plugin::updateConfig()` calls `invalidateCache()`).

## Usage pattern for sidebar widgets

```php
public function __construct() {
    // ... options ...
    parent::__construct();
    $this->registerNoEventOutputHandler('sidebar', 'outputCache');
    $this->registerStandardInvalidators();
}
```

`registerNoEventOutputHandler` attaches `outputCache` to the `sidebar`
`OnOutput` event unless the `no_event` option or `EventForceOff` suppresses
it (see `references/config-and-admin-ui.md`).

Plugins that don't cache simply register a normal output method instead, as
`AuthorBio` does with `registerNoEventOutputHandler('sidebar', 'output')`.
