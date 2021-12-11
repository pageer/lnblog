<?php

namespace LnBlog\Export;

use FileWriteFailed;
use FS;

class ExportTarget
{
    private $fs;
    private $file = '';
    private $content = '';

    public function __construct(FS $fs = null) {
        $this->fs = $fs ?: NewFS();
    }

    public function setExportFile(string $file): void {
        $this->file = $file;
    }

    public function getExportFile(): string {
        return $this->file;
    }

    public function setContent(string $content): void {
        $this->content = $content;
    }

    public function getAsText(): string {
        return $this->content;
    }

    public function save(): void {
        $result = $this->fs->write_file($this->getExportFile(), $this->getAsText());
        if (!$result) {
            throw new FileWriteFailed();
        }
    }
}
