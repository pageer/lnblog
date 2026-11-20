# The event system

LnBlog plugins are primarily event-driven. All events flow through the
`EventRegister` singleton (`lib/EventRegister.php`); objects raise events via
methods inherited from `LnBlogObject` (`lib/LnBlogObject.php`).

## Mechanics

The registry is an in-memory nested array, all keys lowercased:

```
raising class → event name → catching class → ['instance' | 'static'] → handler name(s)
```

- `addEvent($raising_class, $name)` declares an event (auto-called when
  needed).
- `addHandler($raising_class, $name, $catching_class_or_object, $handler, $static = false)`
  attaches a handler. Passing an object stores that object as the invocation
  target; passing a class-name string means the registry will instantiate the
  class when the event fires.
- Handlers are always invoked with the signature `handler($param, $data)`:
  - `$param` — the object that raised the event (or `false` for template
    pseudo-classes).
  - `$data` — an array of extra parameters (often empty).
- The same handler name is not registered twice for the same target.
- Event/handler lookups are case-insensitive (`'blogentry'` == `'BlogEntry'`).

### Raising APIs

| API | Use |
| --- | --- |
| `$obj->raiseEvent($name, ...$extra)` | Standard; extra args become `$data` |
| `$obj->raiseEventAndPassthruReturn($name, array $params)` | Returns the handler's return value (used for feed-listing events; only one callback's result survives) |
| `EventRegister::instance()->activateEventFull($param, $class, $event, $data, &$result)` | Direct, for pseudo-classes or cross-object events; `Publisher` and `SocialWebClient` use this |
| `EventRegister::instance()->activateEvent($obj, $event, $params)` | Object wrapper around `activateEventFull` |

### Instance handlers and object identity (legacy caveat)

When an event fires, `activateEventFull()` walks each catching class:

- If an object was stored at registration, it is used **unless** its actual
  class no longer matches the registered key, in which case (or when no
  object was stored) a **fresh instance is created with `new $classname`**.
- Plugin constructors have side effects (self-registration, config loading),
  so a fresh instance is not necessarily equivalent to the registered one.

This is a legacy behavior inherited from by-name handler registration. The
canonical `getLoader()` pattern (register handlers on `$this` after
construction) avoids the mismatch, and current plugins are written so the
behavior never bites — but it is slated for future cleanup. When writing new
code, register handlers on the live instance, never by class-name string.

## How to discover events (recipes)

The event list changes over time; do not trust any static list blindly.
Verify with:

```sh
# Events raised by core classes (real objects):
grep -rn "raiseEvent(" lib/ --include="*.php"

# Cross-object / pseudo-class events raised directly:
grep -rn "activateEventFull(" lib/ themes/ --include="*.php"

# Feed-listing events with return values:
grep -rn "raiseEventAndPassthruReturn(" lib/ --include="*.php"

# How bundled plugins consume events (target class + event + method):
grep -rn "registerEventHandler\|registerStaticEventHandler\|registerNoEventOutputHandler" plugins/ --include="*.php"
```

The `themes/` matches are the **template pseudo-classes** — synthetic raising
classes that theme templates fire directly at layout points. Any class name
used in `activateEventFull()` from a template is a valid registration target
for plugins, e.g. `$this->registerEventHandler('sidebar', 'OnOutput', 'output')`.

## Snapshot of known events (2026-11)

**Real classes** (raised via `raiseEvent` in `lib/`):

| Class | Events |
| ----- | ------ |
| `Blog` | `OnInit`, `InitComplete`, `OnInsert`, `InsertComplete`, `OnUpdate`, `UpdateComplete`, `OnDelete`, `DeleteComplete`, `OnUpgrade`, `UpgradeComplete`, `ListFeeds` (`Blog::RSS_FEED_EVENT`, via `raiseEventAndPassthruReturn`) |
| `BlogEntry` | `OnInit`, `InitComplete`, `OnUpdate`, `UpdateComplete`, `OnDelete`, `DeleteComplete`, `OnInsert`, `InsertComplete`, `POSTRetreived`, `OnOutput`, `OutputComplete` (two output sites: entry and comment lists), `UploadSuccess`, `ListCommentFeeds` (`BlogEntry::RSS_COMMENT_FEED_EVENT`, via `raiseEventAndPassthruReturn`) |
| `Article` | Raised by `Publisher` under class name `Article`: `OnInsert`, `InsertComplete`, `OnUpdate`, `UpdateComplete`, `OnDelete`, `DeleteComplete` (the `Article` class itself raises none) |
| `BlogComment` | `OnInit`, `InitComplete`, `OnInsert`, `InsertComplete`, `OnUpdate`, `UpdateComplete`, `OnDelete`, `DeleteComplete`, `POSTRetreived`, `OnOutput`, `OutputComplete` |
| `Trackback` | `OnInit`, `InitComplete`, `POSTRetreived`, `OnSend`, `SendComplete`, `OnReceive`, `ReceiveComplete`, `OnOutput`, `OutputComplete`, `OnDelete`, `DeleteComplete` |
| `Pingback` | `OnInit`, `InitComplete`, `OnInsert`, `InsertComplete`, `OnOutput`, `OutputComplete` |
| `Page` | `OnInit`, `InitComplete`, `OnOutput`, `OutputComplete`, `FileEditorReady` (from `EditFilePage`) |
| `FileUpload` | `OnInit`, `InitComplete`, `OnMove`, `MoveComplete` |
| `Blog` (via `WebPages`) | `OnEntryPreview`, `OnArticlePreview` |
| `BlogEntry` (via `WebPages`) | `OnUpdateUiInit`, `UploadError`, `PingbackComplete` (also raised by `SocialWebClient`) |

**Template pseudo-classes** (fired directly by theme templates via
`activateEventFull()`; the parameter is usually `false` or the `Page` object):

| Pseudo-class | Events | Fired from |
| ------------ | ------ | ---------- |
| `sidebar` | `OnOutput`, `OutputComplete` | `basic_layout_tpl.php` (all themes) |
| `banner` | `OnOutput`, `OutputComplete` | layout templates |
| `menubar` | `OnOutput`, `OutputComplete` | layout templates, `include_menubar.php` |
| `commentform` | `BeforeForm`, `FormBegin`, `FormEnd`, `AfterForm` | `comment_form_tpl.php` |
| `posteditor` | `ShowControls` | `entry_edit_tpl.php` |

Sidebar widgets typically register `registerNoEventOutputHandler('sidebar',
'output')`, which attaches to the `sidebar` `OnOutput` event (see
`references/config-and-admin-ui.md` for `no_event` semantics).

**Publishing lifecycle** (`lib/Publisher.php` raises these on `BlogEntry` /
`Article`): `OnInsert` → `InsertComplete`, `OnUpdate` → `UpdateComplete`,
`OnDelete` → `DeleteComplete`, plus `UploadError`/`UploadSuccess` for
attachments.

## Standard invalidation bundle

`Plugin::registerStandardInvalidators()` registers `invalidateCache` for
`blogentry` and `article` `UpdateComplete`/`InsertComplete`/`DeleteComplete`
plus `blog` `UpdateComplete`. Any plugin with cached output should call it.
See `references/caching.md`.
