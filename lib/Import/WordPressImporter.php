<?php

namespace LnBlog\Import;

use Blog;
use BlogComment;
use BlogEntry;
use FS;
use Pingback;
use Publisher;
use SimpleXMLElement;
use SystemConfig;
use UrlPath;
use User;
use WrapperGenerator;
use LnBlog\Storage\BlogRepository;
use LnBlog\Storage\UserRepository;
use LnBlog\Tasks\TaskManager;

# Class: WordPressImporter
# Reads a WordPress export file and imports it into a blog.
#
# This has some limitations in that it doesn't support importing
# media items or unapproved comments/pingbacks.  Importing of users
# is optional and off by default.
class WordPressImporter implements Importer
{
    # Constant: IMPORT_USERS
    # This is an import option indicating that users should be included.
    const IMPORT_USERS = 'import_users';

    private $options = [];
    private $blog;
    private $report;
    private $fs;
    private $wrappers;
    private $task_manager;
    private $user_repo;
    private $blog_repo;
    private $publisher;
    private $user;

    private $user_map;

    public function __construct(
        User $user,
        FS $fs = null,
        WrapperGenerator $wrappers = null,
        TaskManager $task_manager = null,
        UserRepository $user_repo = null,
        BlogRepository $blog_repo = null,
        Publisher $publisher = null
    ) {
        $this->user = $user;
        $this->fs = $fs ?: NewFS();
        $this->wrappers = $wrappers ?: new WrapperGenerator($this->fs);
        $this->task_manager = $task_manager ?: new TaskManager();
        $this->user_repo = $user_repo ?: new UserRepository();
        $this->blog_repo = $blog_repo ?: new BlogRepository();
        $this->report = new ImportReport();
        # This is injected purely for testing purposes.
        # If not passed, we'll create it.
        $this->publisher = $publisher;
        $this->user_map = [];
    }

    # Method: setImportOptions
    # Sets the options for this import
    #
    # Parameters:
    # options - Associative array of option names to values
    public function setImportOptions(array $options): void {
        $this->validateOptions($options);
        $this->options = $options;
    }

    # Method: getImportOptions
    # Gets the current import options.
    #
    # Returns:
    # An associative array of option names to values.
    public function getImportOptions(): array {
        return $this->options;
    }

    # Method: import
    # Takes an export file and imports it into the specified blog.
    #
    # The import will include all published entries, draft entries, static 
    # pages, and approved comments and pingbacks from the export.  If the 
    # "import_users" option is set, it will also create any usernames that
    # do not already exist.  If users are not imported and the usernames do
    # not already exist, then post ownership will be mapped to the blog
    # owner.
    #
    # Parameters:
    # blog - The blog into which data will be imported.
    # source - The source data for the import
    public function import(Blog $blog, FileImportSource $source): void {
        $this->blog = $blog;
        $import_xml = $source->getAsXml();
        $this->importUsers($import_xml);
        $this->importPosts($import_xml);
    }

    # Method: importAsNewBlog
    # This is the same as a regular import, except that it will create a new
    # blog rather than importing into an existing one.
    #
    # In addition to the blog content, this will also import the blog
    # metadata, including the name and description.
    #
    # Parameters:
    # blogid - The short ID used to create the blog
    # paths - The UrlPath for the blog, which includes the URL and filesystem path
    # source - The source data for the import.
    #
    # Returns:
    # The resulting blog object.
    public function importAsNewBlog(string $blogid, UrlPath $paths, FileImportSource $source): Blog {
        $this->blog = new Blog();
        $this->blog->blogid = $blogid;
        $this->blog->home_path = $paths->path();
        $this->blog->default_markup = class_exists('TinyMCEEditor') ? MARKUP_HTML : MARKUP_BBCODE;
        $import_xml = $source->getAsXml();
        SystemConfig::instance()->registerBlog($blogid, $paths);
        $this->importBlogData($import_xml);
        SystemConfig::instance()->writeConfig();
        $this->importUsers($import_xml);
        $this->importPosts($import_xml);
        return $this->blog;
    }

    # Method: getImportReport
    # Get a report of the items that were imported and any errors.
    #
    # Returns:
    # An ImportReport object that details the successes and errors.
    public function getImportReport(): ImportReport {
        return $this->report;
    }

    private function getPublisher(Blog $blog, User $user): Publisher {
        if (!$this->publisher) {
            $this->publisher = new Publisher($blog, $user, $this->fs, $this->wrappers, $this->task_manager);
        }
        $this->publisher->useBlogDefaults(false);
        return $this->publisher;
    }

    private function importBlogData(SimpleXMLElement $xml) {
        $this->blog->name = (string) $xml->channel->xpath('./title')[0];
        $this->blog->description = (string) $xml->channel->xpath('./description')[0];
        $this->blog_repo->save($this->blog);
    }

    private function importUsers(SimpleXMLElement $xml): void {
        $do_import = $this->getImportOptions()[self::IMPORT_USERS] ?? false;

        $authors = $xml->xpath('//wp:author');

        foreach ($authors as $author) {
            $login = (string) $author->xpath('./wp:author_login')[0];
            $uid = (int) $author->xpath('./wp:author_id')[0];
            $name = (string) $author->xpath('./wp:author_display_name')[0];
            $email = (string) $author->xpath('./wp:author_email')[0];
            $this->user_map[$uid] = $login;
            if ($do_import && !$this->user_repo->exists($login)) {
                $user = new User($login);
                $user->password(md5((string)rand()));
                $user->name($name);
                $user->email($email);
                $this->user_repo->createUser($user);
                $this->report->addUser($user);
            }
        }
    }

    private function importPosts(SimpleXMLElement $xml): void {
        $items = $xml->channel->xpath('./item');

        foreach ($items as $item) {
            $post_type = (string)$item->xpath('./wp:post_type')[0];
            $status = (string)$item->xpath('./wp:status')[0];

            $entry = $this->itemToEntry($item);
            $entry_date = new \DateTime();
            $entry_date->setTimestamp($entry->timestamp);

            $user = new User($entry->uid);
            $publisher = $this->getPublisher($this->blog, $user);

            if ($post_type == 'post' && $status == 'publish') {
                try {
                    $publisher->publishEntry($entry, $entry_date);
                    $this->report->addEntry($entry);
                    $this->importComments($entry, $item);
                } catch (\Exception $e) {
                    $this->report->addEntryError($entry, $e);
                }
            } elseif ($status == 'draft') {
                try {
                    $publisher->createDraft($entry, $entry_date);
                    $this->report->addDraft($entry);
                } catch (\Exception $e) {
                    $this->report->addDraftError($entry, $e);
                }
            } elseif ($post_type == 'page') {
                try {
                    $publisher->publishArticle($entry, $entry_date);
                    $this->report->addArticle($entry);
                    $this->importComments($entry, $item);
                } catch (\Exception $e) {
                    $this->report->addArticleError($entry, $e);
                }
            }
        }
    }

    private function itemToEntry(SimpleXMLElement $item): BlogEntry {
        $pub_ts = strtotime((string)$item->xpath('./pubDate')[0]);
        $entry = new BlogEntry();
        $username = (string)$item->xpath('./dc:creator')[0];
        $entry->uid = $this->mapUsername($username);
        $entry->has_html = MARKUP_HTML;
        $entry->post_ts = $pub_ts;
        $entry->timestamp = $pub_ts;
        $entry->subject = (string)$item->xpath('./title')[0];
        $entry->data = (string)$item->xpath('./content:encoded')[0];
        $entry->allow_comment = (string)$item->xpath('./wp:comment_status')[0] == 'open';
        $entry->allow_pingback = (string)$item->xpath('./wp:ping_status')[0] == 'open';
        $entry->article_path = (string)$item->xpath('./wp:post_name')[0];

        $tags = [];
        $post_tags = $item->xpath("./category[@domain='post_tag']");
        foreach ($post_tags as $tag) {
            $tags[] = (string)$tag;
        }
        $entry->tags($tags);

        return $entry;
    }

    private function importComments(BlogEntry $entry, SimpleXMLElement $entry_xml): void {
        $comments_xml = $entry_xml->xpath('./wp:comment');
        foreach ($comments_xml as $xml) {
            $comment_type = (string) $xml->xpath('./wp:comment_type')[0];
            $is_comment = $comment_type == 'comment';
            $pubdate = (string) $xml->xpath('./wp:comment_date')[0];
            $pub_ts = strtotime($pubdate);

            // TODO: We don't currently support unapproved comments.
            // Fix when we change that.
            $is_approved = (int) $xml->xpath('./wp:comment_approved')[0];
            if (!$is_approved) {
                continue;
            }

            if ($is_comment) {
                $comment = new BlogComment();
                $comment->data = (string) $xml->xpath('./wp:comment_content')[0];
                $comment->uid = $this->mapUserId((int)$xml->xpath('./wp:comment_user_id')[0]);
                $comment->name = (string) $xml->xpath('./wp:comment_author')[0];
                $comment->email = (string) $xml->xpath('./wp:comment_author_email')[0];
                $comment->url = (string) $xml->xpath('./wp:comment_author_url')[0];
                $comment->ip = (string) $xml->xpath('./wp:comment_author_IP')[0];
                $comment->post_ts = $pub_ts;
                $comment->timestamp = $pub_ts;
                try {
                    $this->publisher->publishReply($comment, $entry);
                    $this->report->addComment($comment);
                } catch (\Exception $e) {
                    $this->report->addCommentError($comment, $e);
                }
            } else {
                $ping = new Pingback();
                $ping->title = (string) $xml->xpath('./wp:comment_author')[0];
                $ping->data = (string) $xml->xpath('./wp:comment_content')[0];
                $ping->source = (string) $xml->xpath('./wp:comment_author_url')[0];
                $ping->target = $entry->permalink();
                $ping->ping_date = $pubdate;
                $ping->timestamp = $pub_ts;
                $ping->ip = (string) $xml->xpath('./wp:comment_author_IP')[0];
                try {
                    $this->publisher->publishReply($ping, $entry);
                    $this->report->addPing($ping);
                } catch (\Exception $e) {
                    $this->report->addPingError($ping, $e);
                }
            }
        }
    }

    private function mapUsername(string $username): string {
        return $this->user_repo->exists($username) ?
            $username :
            $this->user->username();
    }

    private function mapUserId(int $userid): string {
        $mapped_user = $this->user_map[$userid] ?? '';
        $has_user = ($mapped_user && $this->user_repo->exists($mapped_user));
        return $has_user ? $mapped_user : '';
    }

    private function validateOptions(array $options) {
        $available_options = [
            self::IMPORT_USERS,
        ];
        foreach ($options as $option => $value ) {
            if (!in_array($option, $available_options)) {
                throw new \Exception('Invalid option');
            }
        }
    }
}
