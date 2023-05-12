<?php

namespace LnBlog\Ui;

use Page;
use Path;
use SystemConfig;

class Theme
{
    public const DEFAULT_NAME = 'default';

    private const THEME_DIR = 'themes';
    private const CONFIG_FILE = 'theme.php';

    private string $key;
    private string $name;
    private string $description;
    private $setup;

    // Method: getActiveTheme
    // Gets the metadata for the currently enabled theme.
    public static function getActive(): self {
        $path = '';

        if (defined("THEME_NAME")) {
            $path = Path::mk(
                SystemConfig::instance()->installRoot()->path(),
                self::THEME_DIR,
                THEME_NAME,
                self::CONFIG_FILE
            );
        }

        $result = include $path;

        if (!$result) {
            $result = new self('default', 'Default', '');
        }

        return $result;
    }

    public function __construct(
        string $key,
        string $name,
        string $description,
        ?callable $setup = null
    ) {
        $this->key = $key;
        $this->name = $name;
        $this->description = $description;
        $this->setup = $setup;
    }

    public function getKey(): string {
        return $this->key;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function initialize(Page $page): void {
        if ($this->setup) {
            $callback = $this->setup;
            $callback($page);
        }
    }

    public function isDefault(): bool {
        return $this->name === self::DEFAULT_NAME;
    }
}
