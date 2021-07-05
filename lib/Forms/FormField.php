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
    # Sets the validator function for the field.  The validator should return an array of 
    # error messages related to the field.  If there are no errors, then it should
    # return an empty array.  Note that field-level validators do not
    # account for cross-field dependencies.  That's what form-level validators are for.
    #
    # Validators should have the following signature:
    # --- Code ---
    # function (string $value): string[]
    # ------------
    #
    # Parameters:
    # validator - (callable) A function that takes the raw field value and
    #             returns an array of error messages.
    public function setValidator(callable $validator) {
        $this->validator = $validator;
    }

    # Method: setConverter
    # Sets the converter function for the form value.  This is the function that the 
    # raw value will be run through to get a "processed" value that will be returned
    # by getValue().  The processed value can be anything you want.
    #
    # Converters should have the following signature:
    # --- Code ---
    # function (string $value): mixed
    # ------------
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
        $this->is_validated = true;
        if ($this->validator) {
            $validate = $this->validator;
            $errors = $validate($this->getRawValue());
            $this->is_validated = empty($errors);
            $this->errors = $errors ?: [];
        }
        return $this->is_validated;
    }

    public function getRawValue(): string {
        return $this->raw_value;
    }

    public function setRawValue(string $value) {
        $this->raw_value = $value;
    }

    # Method: getValue
    # Gets the result of running the raw data through the converter.
    # If no converter is defined, the raw value is returned.
    #
    # Returns:
    # The result returned by the converter.  This could be any type.
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
        $this->resolveAndSetOptions($options);
        return $this->renderer->render($this, $pages_obj);
    }

    protected function resolveAndSetOptions(array $options = null) {
        if (isset($options['label'])) {
            $this->renderer->setLabel($options['label']);
            unset($options['label']);
        }

        $this->renderer->setData('SEPARATE_LABEL', $options['sep_label'] ?? false);
        unset($options['sep_label']);

        $this->renderer->setData('LABEL_AFTER', $options['label_after'] ?? false);
        unset($options['label_after']);

        $this->renderer->setData('SUPPRESS_ERRORS', $options['noerror'] ?? false);
        unset($options['noerror']);

        if ($options !== null) {
            $this->renderer->setAttributes($options);
        }
    }
}
