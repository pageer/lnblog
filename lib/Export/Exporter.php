<?php

namespace LnBlog\Export;

interface Exporter
{
    public function setExportOptions(array $options): void;
    public function getExportOptions(): array;
    public function export($entity, ExportTarget $target): void;
}
