<?php

namespace LnBlog\Forms\Renderers;

use BasePages;
use LnBlog\Forms\FormField;
use PHPTemplate;

class SelectRenderer implements FieldRenderer
{
    const TEMPLATE = 'field_select_tpl.php';

    private $attributes = [];
    private $label = '';
    private $data = [];
    private $option_map = [];
    private $default = '';

    public function __construct(array $options = [], string $default = '') {
        $this->option_map = $options;
        $this->default = $default;
    }

    public function setDefault(string $default) {
        $this->default = $default;
    }

    public function setOptions(array $options) {
        $this->option_map = $options;
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
        $template->set('OPTIONS', $this->option_map);
        $template->set('DEFAULT', $this->default);
        $template->set('ERRORS', $field->getErrors());
        foreach ($this->data as $key => $value) {
            $template->set($key, $value);
        }

        return $template->process();
    }
}
