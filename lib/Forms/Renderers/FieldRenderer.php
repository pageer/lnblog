<?php

namespace LnBlog\Forms\Renderers;

use BasePages;
use LnBlog\Forms\FormField;

interface FieldRenderer
{
    public function setAttributes(array $attributes);
    public function setData(string $key, $value);
    public function render(FormField $field, BasePages $pages_obj): string;
}
