<?php

namespace LnBlog\Forms;

use Blog;
use Path;
use PHPTemplate;

trait BlogValidators
{
    protected $fs;
    protected $system_config;

    protected function multiValidator(
        array $validators,
        bool $stop_after_failure = false
    ): callable {
        return function (string $value) use ($validators, $stop_after_failure) {
            $errors = [];
            foreach ($validators as $validator) {
                $current_errors = $validator($value);
                $errors = array_merge($errors, $current_errors);
                if (!empty($current_errors) && $stop_after_failure) {
                    break;
                }
            }
            return $errors;
        };
    }

    protected function pathNotReserved(): callable {
        $registry = $this->system_config->blogRegistry();
        $install_root = $this->system_config->installRoot();
        $userdata = $this->system_config->userData();

        return function (string $path) use ($registry, $install_root, $userdata) {
            // The path can be empty
            if (empty($path)) {
                return [];
            }

            $realpath = $this->fs->realpath($path);

            if ($realpath == $this->fs->realpath($install_root->path())) {
                return [
                    spf_("The blog path you specified is the same as your %s installation path.  This is not allowed, as it will break your installation.  Please choose a different path for your blog.", PACKAGE_NAME)
                ];
            }

            if ($realpath == $this->fs->realpath($userdata->path())) {
                return [
                    spf_("The blog path you specified is the same as your %s userdata path.  This is not supported.", PACKAGE_NAME)
                ];
            }

            foreach ($registry as $blogid => $urlpath) {
                $blog_path = $this->fs->realpath($urlpath->path());
                # If the directory exists, use the real path, otherwise, just take what we're passed.
                $passed_path = $realpath ?: $path;
                if ($passed_path == $blog_path) {
                    return [spf_("The blog path '%s' is already registered.", $path)];
                }
            }

            return [];
        };
    }

    protected function urlNotReserved(): callable {
        $registry = $this->system_config->blogRegistry();
        $install_root = $this->system_config->installRoot();
        $userdata = $this->system_config->userData();

        return function (string $url) use ($registry, $install_root, $userdata) {
            // The url can be empty
            if (empty($url)) {
                return [];
            }

            $url = filter_var($url, FILTER_VALIDATE_URL);
            if (!$url) {
                return [_("The URL provided is not valid.")];
            }

            if ($url == $install_root->url()) {
                return [_("The URL provided is the LnBlog install URL.  This is not valid.")];
            }

            if ($url == $userdata->url()) {
                return [_("The URL provided is the userdata URL.  This is not valid.")];
            }

            foreach ($registry as $blogid => $urlpath) {
                # If the directory exists, use the real path, otherwise, just take what we're passed.
                if ($url == $urlpath->url()) {
                    return [spf_("This URL is already registered to blog %s", $blogid)];
                }
            }

            return [];
        };
    }

    protected function blogidNotReserved(): callable {
        $registry = $this->system_config->blogRegistry();

        return function (string $value) use ($registry) {
            if (isset($registry[$value])) {
                return [spf_("Blog ID %s is already registered", $value)];
            }
            return [];
        };
    }

    protected function blogidExists(): callable {
        $registry = $this->system_config->blogRegistry();

        return function (string $value) use ($registry) {
            if (!isset($registry[$value])) {
                return [spf_("Blog ID %s does not exist", $value)];
            }
            return [];
        };
    }

    protected function pathIsBlog(): callable {
        return function (string $value): array {
            $blog = new Blog($value);
            if (!$blog->isBlog()) {
                return [spf_("The path '%s' is not an LnBlog weblog", $value)];
            }
            return [];
        };
    }

    protected function setCommonValidationData(PHPTemplate $template) {
        $inst_root = $this->system_config->installRoot()->path();
        $default_path = dirname($inst_root);
        if (Path::isWindows()) {
            $default_path = str_replace('\\', '\\\\', $default_path);
        }
        $template->set("DEFAULT_PATH", $default_path);
        $lnblog_name = basename($inst_root);
        $default_url = preg_replace("|$lnblog_name/|i", '', $this->system_config->installRoot()->url());
        $template->set("DEFAULT_URL", $default_url);
        $template->set("PATH_SEP", Path::isWindows() ? '\\\\' : Path::$sep);
    }
}
