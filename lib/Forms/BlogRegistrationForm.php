<?php

namespace LnBlog\Forms;

use Blog;
use FS;
use LnBlog\Forms\Renderers\InputRenderer;
use PHPTemplate;
use SystemConfig;
use UrlPath;

class BlogRegistrationForm extends BaseForm
{
    use BlogValidators;

    const TEMPLATE = 'blog_registration_form_tpl.php';

    private $reg_status = '';

    public function __construct(FS $fs, SystemConfig $config) {
        $this->fs = $fs;
        $this->system_config = $config;

        $this->fields = [
            'register' => new FormField(
                'register',
                new InputRenderer('text'),
                $this->blogidNotReserved()
            ),
            'register_path' => new FormField(
                'register_path',
                new InputRenderer('text'),
                $this->multiValidator(
                    [ $this->pathNotReserved(), $this->pathIsBlog()]
                )
            ),
            'register_url' => new FormField(
                'register_url',
                new InputRenderer('text'),
                $this->urlNotReserved()
            ),
        ];
    }

    protected function doAction() {
        $urlpath = new UrlPath(
            $this->fields['register_path']->getValue(),
            $this->fields['register_url']->getValue()
        );

        try {
            $this->system_config->registerBlog(
                $this->fields['register']->getValue(),
                $urlpath
            );
            $this->system_config->writeConfig();
            $this->reg_status = spf_(
                "Blog %s successfully registered.",
                $this->fields['register']->getValue()
            );
        } catch (FileWriteException $e) {
            $this->reg_status = spf_("Registration error: exited with error: %s", $e->getMessage());
        }
    }

    protected function formValidation(): array {
        foreach ($this->fields as $name => $field) {
            $errors = $field->getErrors();
            if (!empty($errors)) {
                $this->reg_status = $errors[0];
                return $errors;
            }
        }
        return [];
    }

    protected function addTemplateData(PHPTemplate $template) {
        $this->setCommonValidationData($template);
        $template->set('REGISTER_STATUS', $this->reg_status);
    }
}
