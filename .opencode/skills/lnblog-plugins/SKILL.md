---
name: LnBlog Plugins
description: >
  How the LnBlog plugin system works: creating new plugins in the canonical
  style (namespaced class + getLoader()), the event register, plugin
  configuration and the admin UI, plugin pages, output caching, testing, and
  legacy patterns that must not be copied. Use for any task involving
  plugins/, lib/Plugin.php, lib/PluginManager.php, lib/EventRegister.php,
  or plugin events/config/caching.
---

# LnBlog Plugins

LnBlog has a file-based, hook-style plugin system (no database, no composer
packages). A plugin is a single PHP file in a `plugins/` directory. When
included, it either registers a plugin object (and usually event handlers) or
returns a "loader" closure that constructs one. Plugins are auto-discovered
and loaded on **every request** from `blogconfig.php`.

## Canonical rules for new plugins (non-negotiable)

1. **Namespace**: new plugin classes live in `namespace LnBlog\Plugins;`
   and extend the global `\Plugin` class.
2. **Loader style**: the plugin file must end with
   `return ClassName::getLoader();` and the class must define a static
   `getLoader(): callable` that returns a closure constructing the plugin.
   The legacy bottom-of-file `new Recent();` style is deprecated — never
   copy it.
3. **Declaration order in the constructor matters**:
   - Set `plugin_desc` and `plugin_version`.
   - Call `$this->addOption(...)` for every configurable member — options
     declared *after* `parent::__construct()` will never be loaded from
     config, because the base constructor runs `getConfig()` only when
     `member_list` is already populated.
   - Call `parent::__construct()` last (it registers the plugin with the
     manager and loads stored config values into the members).
   - Register event handlers *after* `parent::__construct()`.
4. **No output, ever, during load**: absolutely no characters (not even
   whitespace) outside `<?php ?>` in the plugin file, and no output from the
   constructor. Any output at include time corrupts every page render.
5. **i18n**: user-facing strings go through `_()`, `spf()`, etc. — see
   `references/config-and-admin-ui.md` and the project `AGENTS.md`.
6. **Do not copy legacy idioms**: the `creator_output` guard, `$do_output`
   constructor parameter, and by-name event registration into pseudo-classes
   are legacy. See `references/legacy-and-roadmap.md` before touching them.

## Where plugins live and load order

Plugin directories, in precedence order (first match of a given filename
wins):

1. `{blog root}/plugins/` (per-blog plugins)
2. `{USER_DATA_PATH}/plugins/` (user-installed plugins, independent of the
   distribution)
3. `{INSTALL_ROOT}/plugins/` (bundled plugins)

Only *flat* `*.php` files are scanned — no recursion. Subdirectories such as
`plugins/disabled/` are therefore inert and used to shelve files.

Load order: entries from the `load_first` config list first (in listed
order), then everything else alphabetically, then the hardcoded
`default_load_last` list. Plugins in `exclude_list` are not loaded at all.
Both lists live in `plugins.xml` under the `Plugin_Manager` section and are
edited via the `?action=pluginload` admin page. See
`references/config-and-admin-ui.md`.

## Core architecture at a glance

- `lib/PluginManager.php` — singleton (`PluginManager::instance()`); scans
  directories, builds the load list, includes plugin files, stores instances
  keyed by absolute file path (`getInstanceForFile()`), and owns the merged
  `plugin_config` object.
- `lib/Plugin.php` — abstract base class. `__construct()` self-registers
  with the manager and loads config. Provides the declarative options UI
  (`addOption`, `member_list`, `showConfig`, `updateConfig`), the output
  cache helpers, and the `no_event` option machinery.
- `lib/EventRegister.php` — singleton event registry (raising class →
  event → catching class → handler). Plugins attach callbacks with
  `registerEventHandler()` / `registerStaticEventHandler()`.
- `lib/LnBlogObject.php` — base class that raises events
  (`raiseEvent`, `raiseEventAndPassthruReturn`).
- `lib/BasePages.php` — routes `?plugin=ClassName&do=subroute` URLs to the
  plugin's `outputPage()` method.
- `lib/Plugins/PluginTemplate.php` — `PHPTemplate` subclass with a plugin
  template search path (blog theme → blog → userdata → install theme →
  default theme), so bundled plugins can ship overridable templates under
  `themes/*/templates/plugins/<plugin_dir>/`.

## Quick-start: creating a new plugin

1. Create `plugins/<snake_or_kebab_name>.php` in `{INSTALL_ROOT}/plugins/`.
2. Write the class in the canonical style (see
   `references/plugin-anatomy.md` for a full annotated skeleton and the
   option/control reference).
3. Attach behavior: event handlers (see `references/events.md` — includes
   recipes for discovering existing events), a plugin page via
   `outputPage()` (`references/plugin-pages.md`), and/or cached sidebar
   output (`references/caching.md`).
4. Add unit tests under `tests/unit/Plugins/`
   (`references/testing.md`).
5. Enable it via the admin UI: `index.php?action=pluginload`
   (enable/disable and load order), then configure it via
   `index.php?action=plugins` if it has options.

## Reference files

Read the relevant reference before acting:

| File | Contents |
| ---- | -------- |
| `references/plugin-anatomy.md` | Full anatomy of a plugin file, `member_list`/`addOption` reference, loader mechanics, templates |
| `references/events.md` | Event register mechanics, discovery recipes, snapshot of known events and template pseudo-classes |
| `references/config-and-admin-ui.md` | `plugins.xml` storage, persistence semantics, admin pages, `sys.ini` plugin settings, `no_event` |
| `references/plugin-pages.md` | `outputPage()` routing, `?plugin=&do=` URLs, access control |
| `references/caching.md` | `cachepath`/`buildOutput`/`outputCache`/`invalidateCache` lifecycle |
| `references/testing.md` | Test conventions for plugins |
| `references/legacy-and-roadmap.md` | Legacy patterns, their history, and the confirmed modernization roadmap |
