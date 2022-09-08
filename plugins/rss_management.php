<?php

use LnBlog\Export\BaseFeedExporter;
use LnBlog\Export\ExporterFactory;
use LnBlog\Export\ExportTarget;
use Psr\Log\LoggerInterface;

class RssManagement extends Plugin
{
    const PLUGIN_VERSION = '1.0.0';
    const MAX_ENTRIES_DEFAULT = 10;

    public int $max_entries = self::MAX_ENTRIES_DEFAULT;
    public bool $use_rss1 = false;
    public bool $use_rss2 = true;
    public bool $use_atom = false;
    public bool $use_blog_feeds = true;
    public bool $use_tag_feeds = true;
    public bool $use_comment_feeds = true;

    public bool $show_header_links = true;
    public bool $show_sidebar_links = true;
    public bool $show_tag_links = true;
    public bool $show_comment_links = true;

    public string $sidebar_section_header = '';
    public bool $sidebar_use_icons = true;
    public bool $use_external_feed = false;
    public string $feed_url = '';
    public string $feed_format = '';
    public string $feed_description = '';
    public string $feed_widget = '';

    # Cheap dependency injection for unit tests.
    public ?Blog $test_blog = null;
    public ?BlogEntry $test_entry = null;

    private bool $rethrow_exceptions = false;
    private FS $fs;
    private ExporterFactory $exporterFactory;
    private UrlResolver $urlResolver;
    private System $system;
    private LoggerInterface $logger;

    public static function getLoader(): callable {
        return function (): Plugin {
            return new RssManagement();
        };
    }

    public function __construct(
        FS $fs = null,
        ExporterFactory $exporterFactory = null,
        UrlResolver $urlResolver = null,
        System $system = null,
        LoggerInterface $logger = null
    ) {
        $this->fs = $fs ?: NewFS();
        $this->exporterFactory = $exporterFactory ?: new ExporterFactory();
        $this->urlResolver = $urlResolver ?: new UrlResolver(null, $this->fs);
        $this->system = $system ?: System::instance();
        $this->logger = $logger ?: NewLogger();

        $this->plugin_desc = _('Generate and display RSS feeds for blogs and entries');
        $this->plugin_version = self::PLUGIN_VERSION;

        $this->addOption('max_entries', _('Max number of entries per file'), self::MAX_ENTRIES_DEFAULT, "number");

        $this->addOption('use_rss1', _('Generate RSS 1.0 feeds'), false, "checkbox");
        $this->addOption('use_rss2', _('Generate RSS 2.0 feeds'), true, "checkbox");
        $this->addOption('use_atom', _('Generate Atom feeds'), false, "checkbox");

        $this->addOption('use_blog_feeds', _('Generate feeds for the full blog'), true, "checkbox");
        $this->addOption('use_tag_feeds', _('Generate per-tag feeds'), true, "checkbox");
        $this->addOption('use_comment_feeds', _('Generate comment feeds for each entry'), true, "checkbox");

        $this->addOption('show_header_links', _('Add LINK tags in the page HEAD'), true, "checkbox");
        $this->addOption('show_comment_links', _('Add sidebar links to comment feeds'), true, "checkbox");
        $this->addOption('show_sidebar_links', _('Add an RSS section to the page sidebar'), true, "checkbox");
        $this->addOption('show_tag_links', _('Add feed links in the sidebar tag list'), true, "checkbox");
        $this->addOption("sidebar_section_header", _("Sidebar section heading"), _("News Feeds"));
        $this->addOption("sidebar_use_icons", _("Include RSS icons in sidebar"), true, "checkbox");

        $this->addOption("use_external_feed", _("Use an external RSS feed"), false, "checkbox");
        $this->addOption("feed_url", _("External URL for main feed (e.g. through FeedBurner)"), '', 'text');
        $formats = ExporterFactory::SUPPORTED_FORMATS;
        unset($formats[ExporterFactory::EXPORT_WORDPRESS]);
        $this->addOption(
            "feed_format",
            _("External feed format"),
            ExporterFactory::EXPORT_RSS2,
            'select',
            $formats
        );
        $this->addOption("feed_description", _("External feed description"), _('RSS feed'), 'text');
        $this->addOption(
            "feed_widget",
            _("Custom HTML markup to dipslay instead of text (e.g. for FeedBurner widgets)"),
            '',
            'textarea'
        );

        parent::__construct();

        $this->configureCallbacks();
    }

    public function setTestMode(bool $test_mode): void {
        $this->rethrow_exceptions = $test_mode;
    }

    public function handleCommentUpdate($comment): void {
        if (!$this->use_comment_feeds || !$this->hasValidFormat()) {
            return;
        }

        try {
            $entry = $comment instanceof BlogComment ? $comment->getParent() : $comment;
            $this->generateCommentFeeds($entry);
        } catch (Exception $e) {
            $this->logger->error('Error generating comment feeds', [
                'exception' => $e,
            ]);
            if ($this->rethrow_exceptions) {
                throw $e;
            }
        }
    }

    public function handleEntryUpdate($entry): void {
        if (!$this->use_blog_feeds || !$this->hasValidFormat()) {
            return;
        }

        try {
            $blog = $entry instanceof BlogEntry ? $entry->getParent() : $entry;
            $this->generateBlogFeeds($blog);
        } catch (Exception $e) {
            $this->logger->error('Error generating blog entry feeds', [
                'exception' => $e,
            ]);
            if ($this->rethrow_exceptions) {
                throw $e;
            }
        }
    }

    public function handleTopicUpdate($entry): void {
        if (!$this->use_tag_feeds || !$this->hasValidFormat()) {
            return;
        }

        $tags = [];

        try {
            if ($entry instanceof BlogEntry) {
                $blog = $entry->getParent();
                $tags = $entry->tags();
            } else {
                throw new Exception(_('Entry update event called on non-entry'));
            }

            foreach ($tags as $tag) {
                $this->generateBlogFeeds($blog, $tag);
            }
        } catch (Exception $e) {
            $this->logger->error(_('Error generating blog entry feeds'), [
                'exception' => $e,
            ]);
            if ($this->rethrow_exceptions) {
                throw $e;
            }
        }
    }

    public function handleHeaderLinks(Page $page): void {
        if (!$this->show_header_links) {
            return;
        }

        $display_object = $page->display_object;
        $files = [];

        if ($this->use_external_feed) {
            $files[] = $this->createExternalFeedLink();
        } elseif ($display_object instanceof Blog) {
            /** @var Blog $blog */
            $blog = $display_object;
            $files = $this->getMainFeedsForBlog($blog);
        } elseif ($display_object instanceof BlogEntry) {
            /** @var BlogEntry $entry */
            $entry = $display_object;
            $files = array_merge(
                $this->getCommentRssFeeds($entry),
                $this->getMainFeedsForBlog($entry->getParent())
            );
        }

        foreach ($files as $file) {
            $page->addRSSFeed($file['href'], $file['type'], $file['description']);
        }
    }

    public function handleSidebarLinks($page): void {
        $blog = null;
        $entry = null;
        $object = $page->display_object;
        if ($object instanceof Blog) {
            $blog = $object;
        } elseif ($object instanceof BlogEntry) {
            $entry = $object;
            $blog = $entry->getParent();
        }

        if (!$this->show_sidebar_links || !$blog instanceof Blog) {
            return;
        }

        $template = NewTemplate("sidebar_panel_tpl.php");
        if ($this->sidebar_section_header) {
            $template->set('PANEL_TITLE', $this->sidebar_section_header);
        }
        if (! $this->sidebar_use_icons) {
            $template->set('PANEL_CLASS', 'imglist');
        }

        $items = [];
        if ($this->use_external_feed) {
            $feeds = [$this->createExternalFeedLink()];
        } else {
            $feeds = $this->getMainFeedsForBlog($blog);
            if ($this->show_comment_links && $entry !== null) {
                $feeds = array_merge($feeds, $this->getCommentRssFeeds($entry));
            }
        }

        if ($entry && $this->use_comment_feeds) {
            $feeds[] = [
                'href' => $this->urlResolver->generateRoute('plugin', $blog, [
                    'plugin' => 'rss_management',
                    Plugin::ROUTING_PARAMETER => 'purgecomment',
                    'entry' => $entry->entryID(),
                ]),
                'description' => _('Purge comment feeds'),
                'class' => 'rss-ajax-link',
            ];
            $feeds[] = [
                'href' => $this->urlResolver->generateRoute('plugin', $blog, [
                    'plugin' => 'rss_management',
                    Plugin::ROUTING_PARAMETER => 'regencomment',
                    'entry' => $entry->entryID(),
                ]),
                'description' => _('Regenerate comment feeds'),
                'class' => 'rss-ajax-link',
            ];
        }

        foreach ($feeds as $feed) {
            $items[] = $this->showSidebarLink($feed);
        }

        if ($this->feed_widget) {
            $template->set('PANEL_CONTENT', $this->feed_widget);
            echo $template->process();
        } elseif ($items) {
            $template->set('PANEL_LIST', $items);
            echo $template->process();

            echo '<script type="text/javascript">';
            include Path::mk(__DIR__, 'rss_management', 'shared_js_tpl.php');
            echo '</script>';
        }
    }

    public function handleTagLink($caller, array $data): void {
        $tags = $data[0] ?? null;
        $blog = $data[1] ?? null;
        if (!$this->show_tag_links || !is_array($tags) || !$blog instanceof Blog) {
            return;
        }

        foreach ($tags as $tag) {
            $feeds = $this->getTagFeeds($blog, $tag);
            foreach ($feeds as $feed) {
                $this->showTagLink($feed);
            }
        }
    }

    public function handleBlogFeedListingRequest($caller, array $data): array {
        if (empty($data)) {
            return $this->getMainFeedsForBlog($caller);
        }

        $feeds = [];
        foreach ($data as $tag) {
            $feeds = array_merge($feeds, $this->getTagFeeds($caller, $tag));
        }

        return $feeds;
    }

    public function handleEntryFeedListingRequest($caller): array {
        return $this->getCommentRssFeeds($caller);
    }

    # This is for test double injection
    public function getBlog(): Blog {
        return $this->test_blog ?: new Blog();
    }

    # This is for test double injection
    public function getEntry(): BlogEntry {
        $id = $_GET['entry'] ?? false;
        return $this->test_entry ?: new BlogEntry($id);
    }

    public function outputPage(BasePages $web_page, string $action = ''): void {
        switch ($action) {
            case 'purgeblog':
                $blog = $this->getBlog();
                $this->checkPageAccess($web_page, $blog);
                $this->purgeBlogFeeds($blog);
                break;
            case 'regenblog':
                $blog = $this->getBlog();
                $this->checkPageAccess($web_page, $blog);
                $this->regenerateBlogFeeds($blog);
                break;
            case 'purgecomment':
                $entry = $this->getEntry();
                $this->checkPageAccess($web_page, $entry);
                $this->purgeEntryCommentFeeds($entry);
                break;
            case 'regencomment':
                $entry = $this->getEntry();
                $this->checkPageAccess($web_page, $entry);
                $this->regenerateEntryCommentFeeds($entry);
                break;
            case 'purgeallcomment':
                $blog = $this->getBlog();
                $this->checkPageAccess($web_page, $blog);
                $this->purgeAllCommentFeeds($blog);
                break;
            case 'regenallcomment':
                $blog = $this->getBlog();
                $this->checkPageAccess($web_page, $blog);
                $this->regenerateAllCommentFeeds($blog);
                break;
            default:
                $web_page->getPage()->error(400);
        }
    }

    public function showConfig($page, $csrf_token) {
        $file = Path::mk(__DIR__, 'rss_management', 'admin_tpl.php');

        $BLOG = $this->getBlog();
        $this->showFormHeader($csrf_token);
        include $file;
        $this->showFormFooter();

        return false;
    }

    private function checkPageAccess(BasePages $page, $item): void {
        $user = $page->getCurrentUser();
        if (!$user->checkLogin()) {
            $page->getPage()->error(403);
            throw new RuntimeException(_('User not logged in'));
        }
        if (!$this->system->canModify($item, $user)) {
            $page->getPage()->error(403);
            throw new RuntimeException(_('User not authorized'));
        }
    }

    private function purgeBlogFeeds(Blog $blog): void {
        $rdf_feeds = $this->fs->glob(Path::mk($blog->localpath(), BLOG_FEED_PATH, '*.rdf'));
        $xml_feeds = $this->fs->glob(Path::mk($blog->localpath(), BLOG_FEED_PATH, '*.xml'));
        $feeds = array_merge($rdf_feeds, $xml_feeds);

        foreach ($feeds as $feed) {
            $this->fs->delete($feed);
        }
    }

    private function regenerateBlogFeeds(Blog $blog): void {
        $this->generateBlogFeeds($blog);
        foreach ($blog->tag_list as $tag) {
            $this->generateBlogFeeds($blog, $tag);
        }
    }

    private function purgeEntryCommentFeeds(BlogEntry $entry): void {
        $files = ['comments.rdf', 'comments.xml', 'comments_atom.xml'];

        foreach ($files as $file) {
            $path = Path::mk($entry->localpath(), ENTRY_COMMENT_DIR, $file);
            if ($this->fs->file_exists($path)) {
                $this->fs->delete($path);
            }
        }
    }

    private function regenerateEntryCommentFeeds(BlogEntry $entry): void {
        $this->generateCommentFeeds($entry);
    }

    private function purgeAllCommentFeeds(Blog $blog): void {
        $entries = $blog->getEntries();
        foreach ($entries as $entry) {
            $this->purgeEntryCommentFeeds($entry);
        }
    }

    private function regenerateAllCommentFeeds(Blog $blog): void {
        $entries = $blog->getEntries();
        foreach ($entries as $entry) {
            $this->regenerateEntryCommentFeeds($entry);
        }
    }

    private function generateBlogFeeds(Blog $blog, string $category = ''): void {
        if ($category) {
            $entries = $blog->getEntriesByTag([$category], $this->max_entries);
        } else {
            $entries = $blog->getRecent($this->max_entries);
        }

        $base_url = $blog->uri('base');

        foreach ($this->getEnabledFormats() as $format) {
            $filename = $this->getFilenameForFormat($format, $blog, $category);
            $file_url = $base_url . BLOG_FEED_PATH . '/' . $filename;
            try {
                $target = $this->createFeed($blog, $entries, $format, $filename, $file_url);
                $target->save();
            } catch (Exception $e) {
                $this->logger->error('Error generating feed', [
                    'format' => $format,
                    'filename' => $filename,
                    'file_url'=> $file_url,
                    'exception' => $e,
                ]);
                if ($this->rethrow_exceptions) {
                    throw $e;
                }
            }
        }
    }

    private function generateCommentFeeds(BlogEntry $entry): void {
        $base_url = $entry->uri('comment');

        $items = $entry->getCommentArray();

        foreach ($this->getEnabledFormats() as $format) {
            $filename = $this->getFilenameForFormat($format, $entry);
            $file_url = $base_url . $filename;
            try {
                $target = $this->createFeed($entry, $items, $format, $filename, $file_url);
                $target->save();
            } catch (Exception $e) {
                $this->logger->error('Error generating feed', [
                    'format' => $format,
                    'filename' => $filename,
                    'file_url'=> $file_url,
                    'exception' => $e,
                ]);
                if ($this->rethrow_exceptions) {
                    throw $e;
                }
            }
        }
    }

    private function getEnabledFormats(): array {
        $formats = [];
        if ($this->use_rss1) {
            $formats[] = ExporterFactory::EXPORT_RSS1;
        }
        if ($this->use_rss2) {
            $formats[] = ExporterFactory::EXPORT_RSS2;
        }
        if ($this->use_atom) {
            $formats[] = ExporterFactory::EXPORT_ATOM;
        }

        return $formats;
    }

    private function getFilenameForFormat(string $format, $parent, string $tag = ''): string {
        $name = $parent instanceof Blog ? 'news' : 'comments';

        if ($tag) {
            $name = preg_replace('/\W/', '', $tag) . '_' . $name;
        }

        switch ($format) {
            case ExporterFactory::EXPORT_RSS1:
                $name .= '.rdf';
                break;
            case ExporterFactory::EXPORT_ATOM:
                $name .= '_atom.xml';
                break;
            case ExporterFactory::EXPORT_RSS2:
            default:
                $name .= '.xml';
        }

        return Path::mk(
            $parent->localpath(),
            $parent instanceof Blog ? BLOG_FEED_PATH : ENTRY_COMMENT_DIR,
            $name
        );
    }

    private function createFeed(
        $parent,
        array $items,
        string $format,
        string $filename,
        string $file_url
    ): ExportTarget {
        $options = [
            BaseFeedExporter::OPTION_CHILDREN => $items,
        ];

        $target = new ExportTarget($this->fs);
        $target->setExportUrl($file_url);
        $target->setExportFile($filename);

        $exporter = $this->exporterFactory->create($format);
        $exporter->setExportOptions($options);
        $exporter->export($parent, $target);

        return $target;
    }

    private function hasValidFormat(): bool {
        return $this->use_rss1 || $this->use_rss2 || $this->use_atom;
    }

    private function formatToMimeType(string $format): string {
        switch ($format) {
            case ExporterFactory::EXPORT_ATOM:
                return 'application/atom+xml';
            case ExporterFactory::EXPORT_RSS1:
            case ExporterFactory::EXPORT_RSS2:
            default:
                return 'application/rss+xml';
        }
    }

    private function formatToDescription(string $format, bool $is_comment = false): string {
        switch ($format) {
            case ExporterFactory::EXPORT_RSS1:
                return $is_comment ? _('Comments - RSS 1.0 feed') : _('RSS 1.0 feed');
            case ExporterFactory::EXPORT_ATOM:
                return $is_comment ? _('Comments - Atom feed') : _('Atom feed');
            case ExporterFactory::EXPORT_RSS2:
            default:
                return $is_comment ? _('Comments - RSS 2.0 feed') : _('RSS 2.0 feed');
        }
    }

    private function getMainFeedsForBlog(Blog $blog): array {
        $formats = $this->getEnabledFormats();
        $files = [];
        
        foreach ($formats as $format) {
            $file = $this->getFilenameForFormat($format, $blog);
            if ($this->fs->file_exists($file)) {
                $files[] = $this->createFeedLink($file, $format, false, $blog);
            }
        }

        return $files;
    }

    private function getTagFeeds(Blog $blog, string $tag): array {
        $formats = $this->getEnabledFormats();
        $files = [];
        
        foreach ($formats as $format) {
            $file = $this->getFilenameForFormat($format, $blog, $tag);
            if ($this->fs->file_exists($file)) {
                $files[] = $this->createFeedLink($file, $format, false, $blog);
            }
        }

        return $files;
    }

    private function getIconForFormat(string $format): string {
        switch ($format) {
            case ExporterFactory::EXPORT_RSS1:
                return 'rdf_feed.png';
            case ExporterFactory::EXPORT_RSS2:
            case ExporterFactory::EXPORT_ATOM:
            default:
                return 'xml_feed.png';
        }
    }

    private function getCommentRssFeeds(BlogEntry $entry): array {
        $formats = $this->getEnabledFormats();
        $files = [];
        
        foreach ($formats as $format) {
            $file = $this->getFilenameForFormat($format, $entry);
            if ($this->fs->file_exists($file)) {
                $files[] = $this->createFeedLink($file, $format, true, $entry->getParent(), $entry);
            }
        }

        return $files;
    }

    private function createFeedLink(
        string $file,
        string $format,
        bool $is_comment,
        ?Blog $blog = null,
        ?BlogEntry $entry = null
    ): array {
        return [
            'href' => $this->urlResolver->localpathToUri($file, $blog, $entry),
            'type' => $this->formatToMimeType($format),
            'description' => $this->formatToDescription($format, $is_comment),
            'icon' => getlink($this->getIconForFormat($format)),
        ];
    }

    private function createExternalFeedLink(): array {
        return [
            'href' => $this->feed_url,
            'description' => $this->feed_description,
            'type' => $this->formatToMimeType($this->feed_format),
            'icon' => getlink($this->getIconForFormat($this->feed_format)),
        ];
    }

    /**
     * @param array{'description': string, 'href': string, 'icon': string} $feed
     */
    private function showSidebarLink(array $feed): string {
        $description = $feed['description'];
        $image = $feed['icon'] ?? '';
        $class = $feed['class'] ?? 'news-feed-link';
        ob_start(); ?>
        <a href="<?php echo $feed['href']?>" class="<?php echo $class?>">
    <?php echo htmlspecialchars($description)?>
    <?php if ($image && $this->sidebar_use_icons): ?>
    <img class="feed-icon" alt="<?php echo htmlspecialchars($description)?>" 
         title="<?php echo htmlspecialchars($description)?>" src="<?php echo $image?>" />
    <?php endif ?>
</a>
        <?php
        return ob_get_clean();
    }

    /**
     * @param array{'description': string, 'href': string, 'icon': string} $feed
     */
    private function showTagLink(array $feed): void {
        $description = $feed['description'];
        $image = $feed['icon']; ?>
<a href="<?php echo $feed['href']?>" class="news-feed-link">
    <?php if ($this->sidebar_use_icons): ?>
        <img class="feed-icon" alt="<?php echo htmlspecialchars($description)?>" 
             title="<?php echo htmlspecialchars($description)?>" src="<?php echo $image?>" />
    <?php else: ?>
    (<?php echo htmlspecialchars($description)?>)
    <?php endif ?>
</a><?php
    }

    private function configureCallbacks(): void {
        $this->registerEventHandler("blogcomment", "InsertComplete", "handleCommentUpdate");
        $this->registerEventHandler("blogcomment", "UpdateComplete", "handleCommentUpdate");
        $this->registerEventHandler("blogcomment", "DeleteComplete", "handleCommentUpdate");
        $this->registerEventHandler("blogentry", "InsertComplete", "handleEntryUpdate");
        $this->registerEventHandler("blogentry", "InsertComplete", "handleCommentUpdate");
        $this->registerEventHandler("blogentry", "UpdateComplete", "handleEntryUpdate");
        $this->registerEventHandler("blogentry", "DeleteComplete", "handleEntryUpdate");
        $this->registerEventHandler("blogentry", "InsertComplete", "handleTopicUpdate");
        $this->registerEventHandler("blogentry", "UpdateComplete", "handleTopicUpdate");
        $this->registerEventHandler("blogentry", "DeleteComplete", "handleTopicUpdate");

        $this->registerEventHandler("blog", Blog::RSS_FEED_EVENT, "handleBlogFeedListingRequest");
        $this->registerEventHandler("blogentry", BlogEntry::RSS_COMMENT_FEED_EVENT, "handleEntryFeedListingRequest");

        $this->registerEventHandler("taglist", "ItemOutput", "handleTagLink");
        $this->registerEventHandler("page", "OnOutput", "handleHeaderLinks");
        $this->registerNoEventOutputHandler("sidebar", "handleSidebarLinks");
    }
}

return RssManagement::getLoader();
