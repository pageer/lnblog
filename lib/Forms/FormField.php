<?php

namespace LnBlog\Forms;

use BasePages;
use LnBlog\Forms\Renderers\FieldRenderer;
use LnBlog\Forms\Renderers\InputRenderer;

# Class: FormField
# A base class for form fields.
class FormField
{
    private $name;
    private $renderer;
    private $validator;
    private $converter;

    private $raw_value = '';
    private $converted_value = null;
    private $is_validated = false;
    private $errors = [];

    public function __construct(
        string $name,
        FieldRenderer $renderer = null,
        callable $validator = null,
        callable $converter = null
    ) {
        $this->name = $name;
        $this->renderer = $renderer;
        $this->validator = $validator;
        $this->converter = $converter;
    }

    # Method: setValidator
    # Sets the validator function for the field.
    #
    # Parameters:
    # validator - (callable) A function that takes the raw field value and
    #             returns an array of error messages.  It should return
    #             empty on success.
    public function setValidator(callable $validator) {
        $this->validator = $validator;
    }

    # Method: setConverter
    # Sets the converter function for the form value.
    #
    # Parameters:
    # converter - (callable) A function that takes the raw form value as its
    #             parameter and returns a "converted" value, or any type, that
    #             will be use as the field value.
    public function setConverter(callable $converter) {
        $this->converter = $converter;
    }

    public function setRenderer(FieldRenderer $renderer) {
        $this->renderer = $renderer;
    }

    public function getName(): string {
        return $this->name;
    }

    public function isValidated(): bool {
        return $this->is_validated;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function validate(): bool {
        if ($this->validator) {
            $validate = $this->validator;
            $errors = $validate($this->getRawValue());
            $this->is_validated = empty($errors);
            $this->errors = $errors ?: [];
        } else {
            $this->is_validated = true;
        }
        return $this->is_validated;
    }

    public function getRawValue(): string {
        return $this->raw_value;
    }

    public function setRawValue(string $value) {
        $this->raw_value = $value;
    }

    public function getValue() {
        if ($this->converter) {
            $convert = $this->converter;
            return $convert($this->getRawValue());
        }
        return $this->getRawValue();
    }

    public function render(BasePages $pages_obj, array $options = null): string {
        if (!$this->renderer) {
            $this->renderer = new InputRenderer();
        }
        if ($options !== null) {
            $this->resolveAndSetOptions($options);
        }
        return $this->renderer->render($this, $pages_obj);
    }

    protected function resolveAndSetOptions(array $options) {
        if (isset($options['label'])) {
            $this->renderer->setLabel($options['label']);
            unset($options['label']);
        }
        $this->renderer->setData('SEPARATE_LABEL', $options['sep_label'] ?? false);
        unset($options['sep_label']);
        $this->renderer->setAttributes($options);
    }
}
