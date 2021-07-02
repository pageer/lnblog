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
use LnBlog\Storage\UserRepository;
use LnBlog\Tasks\TaskManager;
use Path;
use PHPTemplate;
use Plugin;
use SystemConfig;
use UrlPath;
use WrapperGenerator;

class BlogImportForm extends BaseForm
{
    const TEMPLATE = 'blog_import_tpl.php';
    const SUCCESS_TEMPLATE = 'blog_import_report_tpl.php';

    private $fs;
    private $system_config;
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
        $inst_root = $this->system_config->installRoot()->path();
        $default_path = dirname($inst_root);
        if (Path::isWindows()) {
            $default_path = str_replace('\\', '\\\\', $default_path);
        }
        $template->set("DEFAULT_PATH", $default_path);
        $lnblog_name = basename($inst_root);
        $default_url = preg_replace("|$lnblog_name/|i", '', $this->system_config->installRoot()->url());
        $template->set("DEFAULT_URL", $default_url);
        $template->set("PATH_SEP", Path::isWindows() ? '\\\\' : Path::$sep);

        $template->set('REPORT', $this->import_report);
        $template->set('BLOG', $this->blog);
    }

    protected function createTemplate(BasePages $pages_obj): PHPTemplate {
        if ($this->import_report) {
            return new PHPTemplate(static::SUCCESS_TEMPLATE, $pages_obj);
        }
        return new PHPTemplate(static::TEMPLATE, $pages_obj);
    }

    private function pathNotReserved(): callable {
        $registry = $this->system_config->blogRegistry();
        $install_root = $this->system_config->installRoot();
        $userdata = $this->system_config->userData();

        return function (string $path) use ($registry, $install_root, $userdata) {
            // The path can be empty
            if (empty($path)) {
                return [];
            }

            $realpath = $this->fs->realpath($path);

            if ($realpath == $this->fs->realpath($install_root->path())) {
                return [
                    spf_("The blog path you specified is the same as your %s installation path.  This is not allowed, as it will break your installation.  Please choose a different path for your blog.", PACKAGE_NAME)
                ];
            }

            if ($realpath == $this->fs->realpath($userdata->path())) {
                return [
                    spf_("The blog path you specified is the same as your %s userdata path.  This is not supported.", PACKAGE_NAME)
                ];
            }

            foreach ($registry as $blogid => $urlpath) {
                $blog_path = $this->fs->realpath($urlpath->path());
                # If the directory exists, use the real path, otherwise, just take what we're passed.
                $passed_path = $realpath ?: $path;
                if ($passed_path == $blog_path) {
                    return [spf_("The blog path '%s' is already registered.", $path)];
                }
            }

            return [];
        };
    }

    private function urlNotReserved(): callable {
        $registry = $this->system_config->blogRegistry();
        $install_root = $this->system_config->installRoot();
        $userdata = $this->system_config->userData();

        return function (string $url) use ($registry, $install_root, $userdata) {
            // The url can be empty
            if (empty($url)) {
                return [];
            }

            $url = filter_var($url, FILTER_VALIDATE_URL);
            if (!$url) {
                return [_("The URL provided is not valid.")];
            }

            if ($url == $install_root->url()) {
                return [_("The URL provided is the LnBlog install URL.  This is not valid.")];
            }

            if ($url == $userdata->url()) {
                return [_("The URL provided is the userdata URL.  This is not valid.")];
            }

            foreach ($registry as $blogid => $urlpath) {
                # If the directory exists, use the real path, otherwise, just take what we're passed.
                if ($url == $urlpath->url()) {
                    return [spf_("This URL is already registered to blog %s", $blogid)];
                }
            }

            return [];
        };
    }

    private function blogidNotReserved(): callable {
        $registry = $this->system_config->blogRegistry();

        return function (string $value) use ($registry) {
            if (isset($registry[$value])) {
                return [spf_("Blog ID %s is already registered", $value)];
            }
            return [];
        };
    }

    private function blogidExists(): callable {
        $registry = $this->system_config->blogRegistry();

        return function (string $value) use ($registry) {
            if (!isset($registry[$value])) {
                return [spf_("Blog ID %s does not exist", $value)];
            }
            return [];
        };
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
