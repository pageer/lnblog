<?php

namespace LnBlog\Storage;

class StorageObject
{
    public string $blogPath;
    public string $itemPath;

    public function __construct(string $blogPath, string $itemPath) {
        $this->blogPath = $blogPath;
        $this->itemPath = $itemPath;
    }
}
