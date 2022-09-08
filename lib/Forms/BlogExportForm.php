<?php

namespace LnBlog\Forms;

use Blog;
use LnBlog\Export\ExporterFactory;
use LnBlog\Export\ExportTarget;
use LnBlog\Forms\Renderers\SelectRenderer;
use LnBlog\Storage\BlogRepository;
use PHPTemplate;
use SystemConfig;

class BlogExportForm extends BaseForm
{
    use BlogValidators;

    const TEMPLATE = 'blog_export_tpl.php';

    private $repository;
    private $exporter_factory;
    private $blog;

    public function __construct(
        ExporterFactory $factory = null,
        SystemConfig $config = null,
        BlogRepository $repo = null
    ) {
        $this->exporter_factory = $factory ?? new ExporterFactory();
        $this->system_config = $config ?? SystemConfig::instance();
        $this->repository = $repo ?? new BlogRepository();

        $blogs = $this->repository->getAll();
        $options = [];
        foreach ($blogs as $blog) {
            $options[$blog->blogid] = $blog->name ?: $blog->blogid;
        }

        $this->fields = [
            'blog_id' => new FormField(
                'blog_id',
                new SelectRenderer($options),
                $this->blogidExists(),
                $this->blogidToBlog()
            ),
            'format' => new FormField(
                'format',
                new SelectRenderer(ExporterFactory::SUPPORTED_FORMATS),
                $this->formatValid()
            ),
        ];
    }

    protected function doAction(): ExportTarget {
        $default_file_name = 'export-' . date('Y-m-d_H_i_s') . '.xml';
        $format = $this->fields['format']->getValue();
        $exporter = $this->exporter_factory->create($format);
        $blog = $this->fields['blog_id']->getValue();
        $export_target = new ExportTarget();
        $export_target->setExportFile($default_file_name);
        
        $exporter->export($blog, $export_target);

        return $export_target;
    }

    protected function addTemplateData(PHPTemplate $template) {
        $this->setCommonValidationData($template);

        $template->set('BLOG', $this->blog);
    }

    private function blogidToBlog(): callable {
        return function (string $value): Blog {
            return $this->repository->get($value);
        };
    }

    private function formatValid(): callable {
        return function (string $value) {
            if (!isset(ExporterFactory::SUPPORTED_FORMATS[$value])) {
                return [spf_('Invalid export format "%s"', $value)];
            }
            return [];
        };
    }
}
