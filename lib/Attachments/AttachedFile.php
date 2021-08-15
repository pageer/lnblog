<?php

namespace LnBlog\Attachments;

use Path;

# Class: AttachedFile
# Represents a file attached to an object, such as an uploaded picture.
class AttachedFile
{
    private $base_path;
    private $name;
    private $url;

    public function __construct(string $path, string $name, string $url) {
        $this->base_path = $path;
        $this->name = $name;
        $this->url = $url;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getPath(): string {
        return Path::mk($this->base_path, $this->name);
    }

    public function getUrl(): string {
        return $this->url;
    }
}
