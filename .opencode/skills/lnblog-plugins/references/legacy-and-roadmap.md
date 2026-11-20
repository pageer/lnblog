# Legacy patterns, history, and modernization roadmap

The plugin system is old (started 2005) and has accreted several styles. This
file records what is legacy, why it exists, and its confirmed disposition.
**Never copy legacy idioms into new code.**

## Confirmed dispositions (as of 2026-11)

| Item | Status / disposition |
| ---- | -------------------- |
| Bottom-of-file instantiation (`new Recent();` at file end) | Legacy. Slated for modernization to the `getLoader()` style. |
| `creator_output` config guard | Legacy, kept only for history. Slated for removal during modernization. |
| `$do_output` constructor parameter | Legacy; part of the same template-embedded-output mechanism. |
| `no_event` option | Historical companion of the above; retained as a user escape hatch. Modern plugins use ordinary event handlers. |
| Global-namespace plugin classes | Legacy. New plugins are namespaced; old ones to be modernized. |
| `plugins/author_bio.php` (global `AuthorBio`) | **Old and dead** duplicate of `plugins/AuthorBio.php` (namespaced). Cleanup pending. |
| `plugins/disabled/template.php` (developer template) | Legacy style; should be rewritten to the canonical style. |
| `EventRegister` re-instantiating handler targets | Legacy behavior; to be fixed. Currently harmless because plugins are written to avoid it. |
| Load-order logic (three overlapping lists) | Evolved over time; refactor planned. |
| CLI plugins running in `web` context (`CONTEXT_CLI` unused in production) | Not currently a problem; fix planned. |
| Broken plugin-name regex in `BasePages` | **Fixed** — now `BasePages::sanitizePluginName()`, properly delimited and unit-tested. Do not copy the old inline `preg_replace('[^A-Za-z0-9_\-]', ...)` idiom from history. |
| Stale `?action=plugin` docblock in `Plugin::outputPage()` | **Fixed** — docblock now documents the real `?plugin=PLUGIN_CLASS` scheme. |
| `getClassNameFromFile()` in `PluginManager` | Unused legacy. |
| `default_excluded` entry `sidebar_googlesearch.php` | Stale — plugin no longer exists. Also `sidebar_news.php` and `sidebar_loginlink.php` in the load-order defaults no longer exist. |
| `FTPFS` (`lib/FS.php` interface) | The FTP implementation was **removed**; the `FS` abstraction remains (`NativeFS`). See history below. |

## History of the template-embedded output mechanism

Originally, plugins could produce output **from their constructor**: templates
would include a line explicitly creating the plugin and telling it to emit
output inline, passing `$do_output = 1` to the constructor (see
`Recent::__construct($do_output = 0)`). Two related pieces existed:

- `creator_output` — a `plugins.xml` key the template was meant to set
  (keyed by plugin) to prevent double output when the template had already
  created the plugin. The write side was never implemented, so the guards in
  old plugins (`if (! plugin_config->value('recent', 'creator_output', 0))`)
  always evaluate true and the plugin self-instantiates on load. **Dead
  logic — do not replicate or extend it.**
- `no_event` (via `addNoEventOption()` /
  `registerNoEventOutputHandler()`) — the option that suppresses event
  handlers so a plugin only outputs when directly invoked. This template-
  embedded style was never really used; the option survives as a user escape
  hatch to turn off a widget's automatic output.

## Event handler target re-instantiation (legacy behavior)

`EventRegister::activateEventFull()` prefers the object stored at
registration, but creates a fresh instance with `new $classname` when no
object was stored or the stored object's class doesn't match the registered
key. Since plugin constructors self-register and load config, a fresh
instance is not guaranteed to match the registered one. Current plugins
register handlers on `$this` (the canonical pattern) and are written so this
never matters, but the behavior should be fixed during the modernization
work. Until then: register handlers on live instances, never by class-name
string, and don't rely on handler identity.

## FTPFS history (why `lib/FS.php` exists)

`FTPFS` once wrote blog files to the web server over FTP. It was a workaround
for hosting providers that enabled `safe_mode` with `mod_php`: the server
process ran under a different account than the one that uploaded the PHP
scripts, so `safe_mode` ownership checks blocked scripts from reading files
they had written. FTP writes preserved ownership consistency. When
`safe_mode` was deprecated and removed from PHP, the feature lost its reason
to exist and was removed. The `FS` interface + `NativeFS` implementation
remain as the file-access abstraction (and per the storage rules in
`AGENTS.md`, all file I/O still goes through it).

## Plugin deployment model

There is no package manager. A plugin is deployed by **copying its PHP file
into a plugin directory** — `{INSTALL_ROOT}/plugins/` for site-wide or
`{blog}/plugins/` for a single blog (or `userdata/plugins/`). Higher-precedence
directories shadow same-named files in lower ones; subdirectories are not
scanned (`plugins/disabled/` is the convention for shelving).

## Modernization roadmap (agreed)

1. All new plugins: `namespace LnBlog\Plugins` + static `getLoader()` +
   `return ClassName::getLoader();`.
2. Modernize bundled legacy plugins to that style; delete the dead
   `author_bio.php` duplicate.
3. Rewrite `plugins/disabled/template.php` to the canonical style.
4. ~~Fix the `BasePages` plugin-name sanitization regex.~~ Done —
   `BasePages::sanitizePluginName()`.
5. ~~Fix the stale `outputPage()` docblock (`?action=plugin`).~~ Done.
6. Refactor the load-order logic (`default_load_first` / `load_first` /
   `default_load_last` split).
7. Address CLI context handling (`Plugin::globalContext()` vs. `CONTEXT_CLI`).
8. Fix `EventRegister` handler re-instantiation semantics.
