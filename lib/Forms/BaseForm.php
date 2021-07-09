<?php

namespace LnBlog\Forms;

use BasePages;
use FormInvalid;
use PHPTemplate;

abstract class BaseForm
{
    const TEMPLATE = 'form_base_tpl.php';

    protected $is_validated = false;
    protected $has_processed = false;
    protected $suppress_csrf_token = false;
    protected $fields = [];
    protected $errors = [];
    protected $method = 'post';
    protected $action = '';
    protected $form_attributes = [];
    protected $row_class = '';
    protected $submit_button_text = 'Submit';
    protected $submit_button_name = 'submit';

    # Method: validate
    # Checks that form input matches the defined validation rules
    public function validate(array $data = null): bool {
        if ($data !== null) {
            $this->setFieldData($data);
        }

        $is_valid = true;
        foreach ($this->fields as $field) {
            $is_valid = $is_valid && $field->validate();
        }

        $form_errors = $this->formValidation();
        $this->errors = $form_errors;
        $is_valid = $is_valid && empty($form_errors);

        $this->is_validated = $is_valid;
        return $this->is_validated;
    }

    # Method: process
    # Processes the form data and takes any action implied by the submission.
    #
    # Parameters:
    # data - (array) The form data to process, e.g. the $_POST array.
    #
    # Returns:
    # The result of the form action, if any, or null.
    public function process(array $data) {
        $this->setFieldData($data);

        $this->validate($data);

        if (!$this->is_validated) {
            throw new FormInvalid($this->getFirstError());
        }

        return $this->doAction();
    }

    # Method: clear
    # Clears the field values.
    public function clear() {
        foreach ($this->fields as $field) {
            $field->setRawValue('');
        }
    }

    public function render(BasePages $pages_obj): string {
        $template = $this->createTemplate($pages_obj);
        $template->set('PAGE', $pages_obj);
        $template->set('METHOD', $this->method);
        $template->set('ACTION', $this->action);
        $template->set('ATTRIBUTES', $this->form_attributes);
        $template->set('ROW_CLASS', $this->row_class);
        $template->set('FIELDS', $this->fields);
        $template->set('ERRORS', $this->errors);
        $template->set('HAS_PROCESSED', $this->has_processed);

        $this->addTemplateData($template);

        // HACK: Because we can't translate in the property initializaiton.
        if ($this->submit_button_text === 'Submit') {
            $this->submit_button_text = _('Submit');
        }
        $template->set('SUBMIT_BUTTON', $this->submit_button_text);
        $template->set('SUBMIT_NAME', $this->submit_button_name);

        $template->set('SUPPRESS_CSRF', $this->suppress_csrf_token);

        return $template->process();
    }

    protected function setFieldData(array $data) {
        # Set the raw values for each field so that we can validate them.
        foreach ($this->fields as $field) {
            $field->setRawValue($data[$field->getName()] ?? '');
        }
        $this->has_processed = true;
    }

    protected function getFirstError(): string {
        foreach ($this->fields as $name => $field) {
            $errors = $field->getErrors();
            if (!empty($errors)) {
                return $name . $errors[0];
            }
        }
        if (!empty($this->errors)) {
            return $this->errors[0];
        }
        return '';
    }

    protected function createTemplate(BasePages $pages_obj): PHPTemplate {
        return new PHPTemplate(static::TEMPLATE, $pages_obj);
    }

    # Method: formValidation
    # Performs form-level validation, i.e. things that are not specific to a
    # single form field.
    #
    # Returns:
    # An array of error messages.  The array is empty on successful validation.
    protected function formValidation(): array {
        return [];
    }

    # Method: doAction
    # Takes whatever action (if any) that is associated with this form.
    protected function doAction() {
    }

    # Method: addTemplateData
    # Adds arbitrary, non-field data to be injected into the template.
    # Parameters:
    # template - (PHPTemplate) The template that the form is rendering.
    protected function addTemplateData(PHPTemplate $template) {

    }
}
