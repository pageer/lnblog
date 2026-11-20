# Plugin configuration and the admin UI

## Storage: `plugins.xml`

Plugin settings persist in XMLINI-format files (parsed by `lib/XMLINI.php`),
never in a database:

| File | Scope |
| ---- | ----- |
| `{USER_DATA_PATH}/plugins.xml` | Installation-wide defaults |
| `{blog root}/plugins.xml` | Per-blog overrides |

`PluginManager::getConfig()` merges the per-blog file **over** the global
one, so per-blog settings win. The merged result is available to all plugins
as `PluginManager::instance()->plugin_config` for the rest of the request —
plugins also read ad-hoc keys directly from it, e.g.:

```php
PluginManager::instance()->plugin_config->value('recent', 'creator_output', 0)
```

Section/key lookups are **case-insensitive** (`XMLINI` lowercases both). The
section name used for a plugin's options is `get_class($this)`, lowercased:

- Global-class plugin `Recent` → section `recent`.
- Namespaced plugin `LnBlog\Plugins\AuthorBio` → section
  `lnblog\plugins\authorbio` (backslash included).

This is by design: let the XML serialization handle the naming and don't
fight the casing.

## Declarative options

`Plugin::$member_list` (populated via `addOption()`) drives both persistence
and the auto-generated admin form. See `references/plugin-anatomy.md` for the
control types.

### Reading (`getConfig()`)

Runs from the base constructor when `member_list` is non-empty. For each
member, `$parser->value(get_class($this), $mem, $config['default'] ?? '')`
fills the public member. Stored values are strings; type-casting is
intentionally left to the plugin (the old int/float casting code was
disabled).

### Writing (`updateConfig()`)

Called by the admin UI on POST (`AdminPages::pluginsetup`). Semantics worth
knowing:

- `checkbox` members store `"1"`/`"0"`; everything else is the raw POST value.
- `file` controls upload via `FileUpload` (to the blog root when
  `BLOG_ROOT` is defined, else `USER_DATA_PATH`).
- **Values that still equal the declared default and have never been saved
  are not written** — so `plugins.xml` only contains explicit overrides. This
  matters because a changed in-code default only takes effect for users who
  never saved that option.
- Targets `{blog}/plugins.xml` in a blog context, else the global file.
- On success the plugin's output cache is invalidated.

## Admin UI

Two distinct admin pages, both routed through `lib/AdminPages.php` (reachable
from both `WebPages` and `AdminPages` action maps):

| URL | Method | What it does |
| --- | ------ | ------------ |
| `index.php?action=plugins` | `AdminPages::pluginsetup()` | Lists every *loaded* plugin class (from the static registry populated by plugin constructors) with version/description; clicking one renders its config form via `showConfig()` and saves via `updateConfig()`. Works globally (admin only) or per-blog (blog modifier). |
| `index.php?action=pluginload` | `AdminPages::pluginloading()` | Enable/disable and load ordering. Writes the `Plugin_Manager` section keys `exclude_list` (disabled) and `load_first` (ordered) to `plugins.xml`. |

Consequences of this design:

- A plugin only appears in the config UI **after it has been loaded** —
  disabled plugins can't be configured until re-enabled.
- Loading behavior lists are configured at global *or* blog level depending
  on where you open the page (`BLOG_ROOT` defined or not).

### System-wide plugin settings (`sys.ini`)

`System::instance()->sys_ini` has a `plugins` section (defaults in
`lib/System.php`):

- `EventDefaultOff` — default value for each plugin's `no_event` option.
- `EventForceOff` — when set, force-suppresses the "no event" fallback
  handlers (`registerNoEventOutputHandler`/`...CompleteHandler`).

### The `no_event` option

`$this->addNoEventOption()` adds a `no_event` checkbox ("No event handlers —
do output when plugin is created"). The companion helpers:

```php
$this->registerNoEventOutputHandler($target, $method);          // OnOutput
$this->registerNoEventOutputCompleteHandler($target, $method);  // OutputComplete
```

register the handler **only if** `no_event` is false and `EventForceOff` is
not set. History: this is the flip side of the legacy `creator_output` /
`$do_output` mechanism — the intent was that with event handlers suppressed,
template authors could embed a direct plugin instantiation to produce output
inline. In practice that template-embedded style was never really used, and
the modern recommendation is ordinary event-driven output; `addNoEventOption`
remains as a user escape hatch to disable a widget's automatic output.

### Custom admin UI

The base class UI is a convenience, not a mandate. Plugins may override
`showConfig()`, `showField()`, `showFormHeader()`/`showFormFooter()`, or
`updateConfig()` to build their own admin interface — `showConfig()` may
return the form markup as a string instead of echoing it. No bundled plugin
has ever done this, but it is supported by design.

## CSRF

The auto-generated config form embeds the CSRF token
(`BasePages::TOKEN_POST_FIELD`) in its hidden field via
`showFormHeader($csrf_token)`; the admin pages validate it. If you override
`showConfig()`, keep that token in your form.
