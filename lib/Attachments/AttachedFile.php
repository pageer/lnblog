<?php

namespace LnBlog\Attachments;

use Path;

# Class: AttachedFile
# Represents a file attached to an object, such as an uploaded picture.
class AttachedFile
{
    private $base_path;
    private $name;

    public function __construct($path, $name) {
        $this->base_path = $path;
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function getPath() {
        return Path::mk($this->base_path, $this->name);
    }
}
