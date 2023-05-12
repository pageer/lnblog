<?php

namespace LnBlog\Plugins;

use BasePages;
use Blog;
use FS;
use LnBlog\Ui\Theme;
use Path;
use PHPTemplate;
use SystemConfig;

class PluginTemplate extends PHPTemplate
{
    private SystemConfig $systemConfig;
    private string $pluginPath;

    public function __construct(
        string $pluginPath,
        string $file,
        BasePages $basePages = null,
        SystemConfig $systemConfig = null
    ) {
        parent::__construct($file, $basePages);
        $this->pluginPath = $pluginPath;
        $this->systemConfig = $systemConfig ?? SystemConfig::instance();
    }

    public function getSearchPath(Blog $blog = null) {
        $blog = $blog ?? NewBlog();
        $inst_root = $this->systemConfig->installRoot()->path();
        $user_data = $this->systemConfig->userData()->path();
        $active_theme = Theme::getActive();

        $ret = [];
        if ($blog->isBlog()) {
            $ret[] = Path::mk($blog->home_path, 'themes', $active_theme->getName(), 'templates', 'plugins', $this->pluginPath);
            $ret[] = Path::mk($blog->home_path, 'templates', 'plugins', $this->pluginPath);
        }
        $ret[] = Path::mk($user_data, 'themes', $active_theme->getName(), 'templates', 'plugins', $active_theme->getName());
        $ret[] = Path::mk($inst_root, 'themes', $active_theme->getName(), 'templates', 'plugins', $this->pluginPath);
        if (!$active_theme->isDefault()) {
            $ret[] = Path::mk($inst_root, 'themes', Theme::DEFAULT_NAME, 'templates', 'plugins', $this->pluginPath);
        }

        return $ret;
    }
}
