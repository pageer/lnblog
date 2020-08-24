<?php

namespace LnBlog\Ui;

class ClientPackage {
    private $name;
    private $js = [];
    private $css = [];
    private $dependencies = [];

    public function __construct(string $name, array $js, array $css, array $dependencies) {
        $this->name = $name;
        $this->js = $js;
        $this->css = $css;
        $this->dependencies = $dependencies;
    }

    public function name(): string {
        return $this->name;
    }

    public function jsFiles(): array {
        return $this->js;
    }

    public function cssFiles(): array {
        return $this->css;
    }

    public function dependencies(): array {
        return $this->dependencies;
    }
}
