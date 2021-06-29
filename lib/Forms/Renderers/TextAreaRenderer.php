<?php

namespace LnBlog\Forms\Renderers;

use BasePages;
use LnBlog\Forms\FormField;
use PHPTemplate;

class TextAreaRenderer implements FieldRenderer
{
    const TEMPLATE = 'field_text_area_tpl.php';

    private $attributes = [];
    private $label = '';
    private $data = [];

    public function __construct() {
    }

    public function setAttributes(array $attrs) {
        $this->attributes = $attrs;
    }

    public function setAttribute(string $name, string $value) {
        $this->attributes[$name] = $value;
    }

    public function setData(string $key, $value) {
        $this->data[$key] = $value;
    }

    public function setLabel(string $label) {
        $this->label = $label;
    }

    public function render(FormField $field, BasePages $pages_obj): string {
        $template = new PHPTemplate(self::TEMPLATE, $pages_obj);
        $template->set('NAME', $field->getName());
        $template->set('VALUE', $field->getRawValue());
        $template->set('LABEL', $this->label);
        $template->set('ATTRIBUTES', $this->attributes);

        return $template->process();
    }
}
