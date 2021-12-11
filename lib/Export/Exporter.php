<?php

namespace LnBlog\Export;

use Blog;

interface Exporter
{
    public function setExportOptions(array $options): void;
    public function getExportOptions(): array;
    public function export(Blog $blog, ExportTarget $target): void;
}
