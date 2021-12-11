<?php

namespace LnBlog\Forms;

use BasePages;
use Blog;
use FS;
use LnBlog\Forms\Renderers\InputRenderer;
use LnBlog\Forms\Renderers\SelectRenderer;
use LnBlog\Forms\Renderers\TextAreaRenderer;
use LnBlog\Import\FileImportSource;
use LnBlog\Import\ImporterFactory;
use LnBlog\Import\WordPressImporter;
use LnBlog\Storage\BlogRepository;
use LnBlog\Tasks\TaskManager;
use PHPTemplate;
use Plugin;
use SystemConfig;
use UrlPath;
use WrapperGenerator;

class BlogImportForm extends BaseForm
{
    use BlogValidators;

    const TEMPLATE = 'blog_import_tpl.php';
    const SUCCESS_TEMPLATE = 'blog_import_report_tpl.php';

    private $repository;
    private $importer_factory;
    private $import_report;
    private $blog;

    public function __construct(FS $fs, SystemConfig $config = null, BlogRepository $repo = null) {
        $this->fs = $fs;
        $this->system_config = $config ?? SystemConfig::instance();
        $this->repository = $repo ?? new BlogRepository();

        $blogs = $this->repository->getAll();
        $options = [];
        foreach ($blogs as $blog) {
            $options[$blog->blogid] = $blog->name ?: $blog->blogid;
        }

        $this->fields = [
            'new_blog_id' => new FormField(
                'new_blog_id',
                new InputRenderer('text'),
                $this->blogidNotReserved()
            ),
            'new_blog_path' => new FormField(
                'new_blog_path',
                new InputRenderer('text'),
                $this->pathNotReserved()
            ),
            'new_blog_url' => new FormField(
                'new_blog_url',
                new InputRenderer('text'),
                $this->urlNotReserved()
            ),
            'import_to' => new FormField(
                'import_to',
                new SelectRenderer($options),
                $this->blogidExists(),
                $this->blogidToBlog()
            ),
            'import_users' => new FormField(
                'import_users',
                new InputRenderer('checkbox'),
            ),
            'import_text' => new FormField(
                'import_text',
                new TextAreaRenderer(),
                $this->validImport(),
                $this->textToFileSource()
            ),
            'import_option' => new FormField(
                'import_option',
                new InputRenderer('hidden'),
                $this->isInList(['new', 'existing'])
            ),
        ];

        $this->fields['import_option']->setRawValue('new');
    }

    protected function formValidation(): array {
        return [];
    }

    protected function doAction(): Blog {
        $current_context = Plugin::globalContext();
        Plugin::globalContext(Plugin::CONTEXT_IMPORT);

        $importer = $this->getImporterFactory()->create(NewUser(), ImporterFactory::IMPORT_WORDPRESS);
        $importer->setImportOptions(
            [
                WordPressImporter::IMPORT_USERS => $this->fields['import_users']->getValue(),
            ]
        );

        if ($this->fields['import_option']->getValue() == 'new') {
            $urlpath = new UrlPath(
                $this->fields['new_blog_path']->getValue(),
                $this->fields['new_blog_url']->getValue(),
            );
            $blog = $importer->importAsNewBlog(
                $this->fields['new_blog_id']->getValue(),
                $urlpath,
                $this->fields['import_text']->getValue()
            );
        } else {
            $blog = $this->fields['import_to']->getValue();
            $importer->import(
                $this->fields['import_to']->getValue(),
                $this->fields['import_text']->getValue()
            );
        }

        $this->import_report = $importer->getImportReport();
        $this->blog = $blog;

        Plugin::globalContext($current_context);

        return $blog;
    }

    protected function addTemplateData(PHPTemplate $template) {
        $this->setCommonValidationData($template);

        $template->set('REPORT', $this->import_report);
        $template->set('BLOG', $this->blog);
    }

    protected function createTemplate(BasePages $pages_obj): PHPTemplate {
        if ($this->import_report) {
            return new PHPTemplate(static::SUCCESS_TEMPLATE, $pages_obj);
        }
        return new PHPTemplate(static::TEMPLATE, $pages_obj);
    }


    private function isInList(array $allowed_values): callable {
        return function (string $value) use ($allowed_values) {
            if (!in_array($value, $allowed_values)) {
                return [_('No valid import option selected')];
            }
            return [];
        };
    }

    private function blogidToBlog(): callable {
        return function (string $value): Blog {
            return $this->repository->get($value);
        };
    }

    private function validImport(): callable {
        return function (string $value): array {
            $source = $this->createFileImportSource($value);
            if (!$source->isValid()) {
                return [_('Invalid import data')];
            }
            return [];
        };
    }

    private function textToFileSource(): callable {
        return function (string $value): FileImportSource{
            return $this->createFileImportSource($value);
        };
    }

    private function createFileImportSource(string $text): FileImportSource {
        $source = new FileImportSource($this->fs);
        $source->setText($text);
        return $source;
    }

    private function getImporterFactory(): ImporterFactory {
        if (!$this->importer_factory) {
            $this->importer_factory = new ImporterFactory(
                $this->fs,
                new WrapperGenerator($this->fs),
                new TaskManager()
            );
        }
        return $this->importer_factory;
    }
}
