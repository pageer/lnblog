<?php

namespace LnBlog\Ui;

use Page;

class VendorPackages {
    private $available_packages = [];
    private $requested_packages = [];

    public function __construct(bool $useCdn = false) {
        $this->initializepackages($useCdn);
    }

    # Method: addPackage
    # Adds a given third-party package to the page.
    #
    # Parameters:
    # - name
    public function addPackage(string $name) {
        if (!isset($this->available_packages[$name])) {
            throw new \RuntimeException("Unknown package $name");
        }

        $package = $this->available_packages[$name];

        if (!isset($this->requested_packages[$name])) {
            foreach ($package->dependencies() as $dep) {
                $this->addPackage($dep);
            }
            $this->requested_packages[$name] = $package;
        }
    }

    # Method: addSelectedPackagesToPage
    public function addSelectedPackagesToPage(Page $page) {
        // TODO: This is a dity hack - fix it!
        $scripts = $page->scripts;
        $stylesheets = $page->stylesheets;
        $page->scripts = [];
        $page->stylesheets = [];

        foreach ($this->requested_packages as $package) {
            $this->addFilesToPage($package, $page);
        }

        $page->scripts = array_merge($page->scripts, $scripts);
        $page->stylesheets = array_merge($page->stylesheets, $stylesheets);
    }

    private function initializepackages(bool $use_cdn) {
        $jquery_url = $use_cdn ? '//code.jquery.com/jquery-1.11.3.min.js' : 'jquery.min.js';
        $jquery_ui_url = $use_cdn ? '//code.jquery.com/ui/1.11.4/jquery-ui.min.js' : 'jquery-ui.min.js';
        $tiny_mce_url = $use_cdn ? '//cdn.tinymce.com/4/tinymce.min.js' : 'tinymce.min.js';
        $theme = defined('JQUERYUI_THEME') ? JQUERYUI_THEME : DEFAULT_JQUERYUI_THEME;
        $jquery_stylesheets = $use_cdn ? 
            ["//code.jquery.com/ui/1.11.4/themes/$theme/jquery-ui.css"] :
            ['jquery-ui.min.css', 'jquery-ui.structure.min.css', 'jquery-ui.theme.min.css'];
        $packages = [
            new ClientPackage('jquery', [$jquery_url], [], []),
            new ClientPackage('jquery-form', ['jquery.form.js'], [], ['jquery']),
            new ClientPackage(
                'jquery-datetime-picker',
                ['jquery.datetime.picker.js'],
                ['jquery.datetime.picker.css'],
                ['jquery']
            ),
            new ClientPackage('dropzone', ['dropzone.js'], ['dropzone.css'], ['jquery']),
            new ClientPackage(
                'jquery-ui',
                [$jquery_ui_url],
                $jquery_stylesheets,
                ['jquery']
            ),
            new ClientPackage('tag-it', ['tag-it.js'], ['jquery.tagit.css'], ['jquery-ui']),
            new ClientPackage('tinymce', [$tiny_mce_url], [], []),
        ];

        foreach ($packages as $package) {
            $this->available_packages[$package->name()] = $package;
        }
    }

    private function addFilesToPage(ClientPackage $package, Page $page) {
        foreach ($package->jsFiles() as $file) {
            if (strpos($file, '//') === false) {
                $page->addScript($file);
            } else {
                $page->addExternalScript($file);
            }
        }
        foreach ($package->cssFiles() as $file) {
            if (strpos($file, '//') === false) {
                $page->addStylesheet($file);
            } else {
                $page->addExternalStylesheet($file);
            }
        }
    }
}
