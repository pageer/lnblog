# Plugin anatomy

Reference for the structure of an LnBlog plugin file. The canonical example
plugins are `plugins/AuthorBio.php` (namespaced sidebar widget) and
`plugins/rss_management.php` (plugin page with many options and dependency
injection).

## File location and precedence

| Directory | Purpose |
| --------- | ------- |
| `{blog root}/plugins/` | Per-blog plugins; highest precedence |
| `{USER_DATA_PATH}/plugins/` | User-installed plugins, independent of the distribution |
| `{INSTALL_ROOT}/plugins/` | Plugins bundled with LnBlog |

`PluginManager::getFileList()` scans these in that order and collects flat
`*.php` files only. `array_unique()` + `sort()` means:

- A filename found in a higher-precedence directory **shadows** the same
  filename in lower-precedence directories (the lower one is never loaded).
- Subdirectories are never scanned. `plugins/disabled/` exists only to shelve
  inactive files; anything in it is inert.

The scan runs on every request from `blogconfig.php` via
`PluginManager::instance()->loadPlugins()`.

## Canonical plugin file

```php
<?php
# Plugin: Human readable name
# Short description shown in docs/comments.

namespace LnBlog\Plugins;

use PHPTemplate;

class MyWidget extends \Plugin
{
    const PLUGIN_DIR = 'my_widget';       // subdir under themes/*/templates/plugins/
    const TEMPLATE = 'panel_tpl.php';

    /**
     * @codeCoverageIgnore
     */
    public static function getLoader(): callable {
        return function (): \Plugin {
            return new self();
        };
    }

    public function __construct() {
        // 1. Identity
        $this->plugin_version = '0.1.0';
        $this->plugin_desc = _('Description shown in the plugin admin list.');

        // 2. Options — MUST come before parent::__construct()
        $this->addOption('title', _('Sidebar box title'), _('My Widget'));
        $this->addNoEventOption();   // if the plugin does event-driven output

        // 3. Base constructor: registers plugin + loads stored config
        parent::__construct();

        // 4. Event handlers — only after parent::__construct()
        $this->registerNoEventOutputHandler('sidebar', 'output');
    }

    public function output() {
        $tpl = new PHPTemplate('sidebar_panel_tpl.php');
        $tpl->set('PANEL_TITLE', $this->title);
        $tpl->set('PANEL_CONTENT', '...');
        $tpl->output();
    }
}

// @codeCoverageIgnoreStart
return MyWidget::getLoader();
// @codeCoverageIgnoreEnd
```

### Why the ordering rules exist

- `Plugin::__construct()` calls `PluginManager::instance()->registerPlugin($this)`
  (adds the class to the registry that drives the admin UI) and then runs
  `getConfig()` **only if `$this->member_list` is already non-empty**.
  Options declared after `parent::__construct()` therefore exist but are
  never populated from `plugins.xml`.
- Handlers are registered after the base constructor so that the object is
  fully configured (all option values loaded) before any handler can fire.

### Why `getLoader()` is required

`PluginManager::loadPlugins()` `include`s each plugin file. If the include
**returns a callable**, the manager calls it and stores the returned instance
in `instance_list`, keyed by the plugin file's absolute path
(`PluginManager::getInstanceForFile()`). That stored instance is what the
plugin-page router (`lib/BasePages.php`) reuses, so a plugin hit via a
`?plugin=...` URL is the same object that ran during page load.

The legacy alternative — instantiating at the bottom of the file with no
return value — still loads, but the router cannot reuse the instance and will
`require` the file again (a no-op for the class declaration) and construct a
**second** object for the page request. That legacy style is deprecated; see
`references/legacy-and-roadmap.md`.

## Options and `member_list`

`addOption($name, $description, $default, $control = 'text', $options = false)`:

- Declares a public member `$this->$name` initialized to `$default`.
- Adds an entry to `$this->member_list[$name]` with `description`, `default`,
  `control`, and optionally `options`.

The `member_list` array drives the auto-generated admin config form
(`showConfig()`/`showField()`) and persistence (`updateConfig()`/`getConfig()`).
You can also populate `member_list` directly instead of using `addOption()`.

### Control types (`showField()` in `lib/Plugin.php`)

| Control | Rendered as | Notes |
| ------- | ----------- | ----- |
| `text` (default) | `<input type="text">` | |
| `number` | `<input type="number">` | Falls through the generic branch; type string is used as the input type |
| `checkbox` | `<input type="checkbox">` | Stored as `"1"`/`"0"` |
| `radio` | fieldset of radios | Requires `options` array (value ⇒ label) |
| `select` | `<select>` | Requires `options` array (value ⇒ label) |
| `textarea` | `<textarea>` | |
| `file` | text input + `<input type="file">` | Upload handled by `FileUpload`; file lands in the blog root, or `USER_DATA_PATH` outside a blog context |

Options arrays for `radio`/`select` are often built from existing constants,
e.g. `TextProcessor::getFilterList()` for markup formats.

### Reading options at runtime

Option values are plain public members after construction:
`$this->title`, `$this->num_entries`, etc.

## Templates

Two template mechanisms:

- `PHPTemplate('name_tpl.php')` — the regular theme template loader; used for
  shared panels such as `sidebar_panel_tpl.php`.
- `LnBlog\Plugins\PluginTemplate($pluginDir, $file)` — a `PHPTemplate`
  subclass with a **plugin-specific** search path, letting a plugin ship its
  own overridable templates:

  1. `{blog}/themes/{active}/templates/plugins/{dir}/`
  2. `{blog}/templates/plugins/{dir}/`
  3. `{userdata}/themes/{active}/templates/plugins/{active theme}/`
  4. `{INSTALL_ROOT}/themes/{active}/templates/plugins/{dir}/`
  5. `{INSTALL_ROOT}/themes/default/templates/plugins/{dir}/` (when the
     active theme is not the default)

  Bundled plugin templates live in `themes/{theme}/templates/plugins/<dir>/`.

## Docs/comments conventions in plugin files

- Header comment block starting `# Plugin: <Name>` describing the plugin.
- Inline `#`-comments document each option (`# Option: ...`), following the
  existing bundled plugins.
- `@codeCoverageIgnore` on `getLoader()` and
  `@codeCoverageIgnoreStart`/`@codeCoverageIgnoreEnd` around the trailing
  `return ...::getLoader();` statement.

## Naming

- Class names are PascalCase (`Recent`, `RssManagement`, `AuthorBio`).
- Bundled file names are mostly snake_case (`sidebar_recent.php`) or
  PascalCase matching the class (`AuthorBio.php`, new style).
- `PluginManager::getClassNameFromFile()` (deriving a class name from the
  filename) is unused legacy — do not rely on filename↔class correspondence.

## The instructional template

`plugins/disabled/template.php` is the developer-facing plugin template. It
is written in the **legacy** style (global class, `return function (): Plugin
{...}` closure instead of a static `getLoader()`); consult it for the
explanatory comments, but follow the canonical structure above for new code.
