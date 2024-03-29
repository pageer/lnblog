<?php

use LnBlog\Model\Reply;
use LnBlog\Tasks\AutoPublishTask;
use LnBlog\Tasks\TaskManager;

# Class: Publisher
# Handles publication of blog entries, including updates and deletions.
class Publisher
{

    private $fs;
    private $blog;
    private $user;
    private $wrappers;
    private $task_manager;
    private $http_client;
    private $keepHistory;
    private $use_blog_defaults = true;

    public function __construct(
        Blog $blog,
        User $user,
        FS $fs,
        WrapperGenerator $wrappers,
        TaskManager $task_manager
    ) {
        $this->blog = $blog;
        $this->user = $user;
        $this->fs = $fs;
        $this->wrappers = $wrappers;
        $this->task_manager = $task_manager;
    }

    /* Method: keepEditHistory
       Set or get whether publication should retain edit history.

       Parameters:
       val - Boolean for whether to turn on edit history or null for fetch.

       Returns:
       True if edit history is on, false otherwise.
    */
    public function keepEditHistory($val = null) {
        if ($val !== null) {
            $this->keepHistory = $val;
        }
        return $this->keepHistory;
    }

    public function useBlogDefaults($use_defaults = null) {
        if ($use_defaults !== null) {
            $this->use_blog_defaults = $use_defaults;
        }
        return $this->use_blog_defaults;
    }

    /* Method: setUser
       Set the user to publish as.
    */
    public function setUser(User $user) {
        $this->user = $user;
    }

    /* Method publishEntry
       Publish the entry as a normal blog entry.

       Puts the entry inside the normal list of blog entries.
       Throws on failure or if the entry is already published.

       Paremeters:
       entry - (BlogEntry) The entry to publish
       time  - (DateTime) Optional publication time, default is now.

       Throws:
       Exception or EntryRenameFailed when rename fails.
     */
    public function publishEntry(BlogEntry $entry, DateTime $time = null) {
        $time = $time ?: new DateTime();
        $curr_ts = $time->getTimestamp();

        $basepath = Path::mk($this->blog->home_path, BLOG_ENTRY_PATH);
        $dir_path = Path::mk($basepath, $entry->getPath($curr_ts));
        $draft_created = false;

        // Entry might not already exist if created through an API call.
        if (! $entry->isEntry()) {
            $this->createDraft($entry, $time);
            $draft_created = true;
        }

        # If the entry directory already exists, something is wrong.
        if ( $this->fs->is_dir($dir_path) ) {
            $dir_path = Path::mk($basepath, $entry->getPath($curr_ts, false, true));
        }

        if ( $this->fs->is_dir($dir_path) ) {
            throw new TargetPathExists(spf_("Path already exists: %s", $dir_path));
        }

        $this->raiseEvent($entry, 'BlogEntry', 'OnInsert');

        $this->createMonthDirectory($dir_path);
        $this->renameEntry(dirname($entry->file), $dir_path);
        $this->createEntryWrappers($dir_path);

        $this->updateFileForPublication($entry, $dir_path, $curr_ts, $draft_created);

        $entry->makePrettyPermalink();
        $this->blog->updateTagList($entry->tags());
        $this->sendPingbacks($entry);

        $this->raiseEvent($entry, 'BlogEntry', 'InsertComplete');
    }


    /* Method: publishArticle
       Publish the entry as an article.

       Same as publishEntry(), but publishes the entry as an article outside
       of the normal blog listing structure.  Throws on failure.

       Parameters:
       entry - (BlogEntry) The entry to publish
       time  - (DateTime) Optional publication time, default is now.

       Throws:
       Exception or EntryRenameFailed
     */
    public function publishArticle(BlogEntry $entry, DateTime $time = null) {

        $time = $time ?: new DateTime();
        $curr_ts = $time->getTimestamp();
        $basepath = Path::mk($this->blog->home_path, BLOG_ARTICLE_PATH);
        $draft_created = false;

        if (! $entry->isEntry()) {
            $this->createDraft($entry, $time);
            $draft_created = true;
        }

        $dir_path = $this->getArticleDirectoryPath($entry, $basepath);

        if ($this->fs->is_dir($dir_path)) {
            throw new EntryAlreadyExists();
        } elseif (! $this->fs->is_dir($basepath)) {
            $this->wrappers->createDirectoryWrappers($basepath, WrapperGenerator::BLOG_ARTICLES);
        }

        $this->raiseEvent($entry, "Article", "OnInsert");

        $this->renameEntry(dirname($entry->file), $dir_path);

        $ret = $this->wrappers->createDirectoryWrappers($dir_path, WrapperGenerator::ARTICLE_BASE);
        $this->wrappers->createDirectoryWrappers(Path::mk($dir_path, ENTRY_COMMENT_DIR), WrapperGenerator::ENTRY_COMMENTS);
        $this->wrappers->createDirectoryWrappers(Path::mk($dir_path, ENTRY_TRACKBACK_DIR), WrapperGenerator::ENTRY_TRACKBACKS);
        $this->wrappers->createDirectoryWrappers(Path::mk($dir_path, ENTRY_PINGBACK_DIR), WrapperGenerator::ENTRY_PINGBACKS);

        $this->updateFileForPublication($entry, $dir_path, $curr_ts, $draft_created);

        $entry->setSticky($entry->is_sticky);
        $this->blog->updateTagList($entry->tags());
        $this->sendPingbacks($entry);

        $this->raiseEvent($entry, "Article", "InsertComplete");
    }

    /* Method: unpublish
       Unpublish the entry.

       Moves the entry from a published blog entry or article to an unpublished
       draft entry.  Throws on failure or if the entry is not published.

       Parameters:
       entry - (BlogEntry) The entry to unpublish

       Throws:
       Exception or EntryRenameFailed
     */
    public function unpublish(BlogEntry $entry) {
        if (! $entry->isEntry()) {
            throw new EntryDoesNotExist();
        }
        if (! $entry->isPublished()) {
            throw new EntryIsNotPublished();
        }

        $this->raiseEvent($entry, 'BlogEntry', 'OnDelete');

        $path = date('d_His', $entry->post_ts);
        $draft_path = Path::mk($this->blog->home_path, BLOG_DRAFT_PATH, $path);
        $this->renameEntry($entry->localpath(), $draft_path);

        $this->wrappers->removeForEntry($entry);
        $this->deletePermalinkFile($entry);

        $this->raiseEvent($entry, 'BlogEntry', 'DeleteComplete');
    }

    /* Method: createDraft
       Save the entry as a new draft.

       Saves a new entry in the drafts folder.  Throws on failure or
       if the entry already exists.

       Parameters:
       entry - (BlogEntry) The entry to persist as a draft.
       time  - (DateTime) Optional creation time

       Throws:
       CouldNotCreateDirectory or EntryAlreadyExists or EntryWriteFailed
     */
    public function createDraft(BlogEntry $entry, DateTime $time = null) {
        $time = $time ?: new DateTime();
        $ts = $time->getTimestamp();
        $draft_path = Path::mk($this->blog->home_path, BLOG_DRAFT_PATH);

        $this->createDraftsDirectory($draft_path);

        if ($entry->isEntry()) {
            throw new EntryAlreadyExists("This draft aleady exists.");
        }

        $path = $this->getEntryDraftPath($entry, $ts, $draft_path);
        $tries = 0;
        // HACK: If a draft for this timestamp already exists, add a minute
        // and try again.  Need a better way to do this.
        while ($this->fs->file_exists($path) && $tries < 100) {
            $path = $this->getEntryDraftPath($entry, $ts + (++$tries * 60), $draft_path);
        }
        $this->createEntryDraftDirectory($path);

        if ($this->use_blog_defaults) {
            $this->applyBlogDefaults($entry);
        }

        $entry->file = Path::mk($path, ENTRY_DEFAULT_FILE);
        $entry->uid = $this->user->username();
        $entry->setDates($ts);
        $entry->permalink_name = $entry->calcPrettyPermalink();

        $file_data = $entry->serializeXML();
        $ret = $this->fs->write_file($entry->file, $file_data);

        $this->handleUploads($entry);

        if ($entry->autopublish) {
            $this->setAutoPublish($entry);
        }

        if (! $ret) {
            throw new EntryWriteFailed("Failed to write draft entry data.");
        }
    }

    /* Method: update
       Update an existing entry on disk.

       Writes out the state of the entry without changing its publication status.
       Throws if the update fails or the entry does not already exist.

       Parameters:
       entry - (BlogEntry) The entry to update.
       time  - (DateTime) The optional time when the entry is updated.

       Throws:
       EntryDoesNotExist or EntryWriteFailed or EntryRenameFailed
     */
    public function update(BlogEntry $entry, DateTime $time = null) {
        $time = $time ?: new DateTime();

        if (! $this->fs->file_exists($entry->file)) {
            throw new EntryDoesNotExist();
        }

        $event_class = $entry->isArticle() ? 'Article' : 'BlogEntry';

        if ($entry->isPublished()) {
            $this->raiseEvent($entry, $event_class, 'OnUpdate');
        }

        $entry->ip = get_ip();
        $entry->setDates($time->getTimestamp());
        $entry->permalink_name = $entry->calcPrettyPermalink();

        $this->updateEntry($entry, $time);

        $this->updateAutoPublishState($entry);
        $this->handleUploads($entry);
        $this->updatePermalinkFile($entry);

        if ($entry->isArticle()) {
            $entry->setSticky($entry->is_sticky);
        }

        if ($entry->isPublished()) {
            $this->blog->updateTagList($entry->tags());
            $this->sendPingbacks($entry);
            $this->raiseEvent($entry, $event_class, 'UpdateComplete');
        }
    }

    /* Method: delete
       Completely delete an entry.

       Applies to both published and draft entries.  Throws on failure of if
       the entry has not been saved.

       Parameters:
       entry - (BlogEntry) The entry to delete.
       time  - (DateTime) The optional deletion time, used for history.

       Throws:
       EntryDoesNotExist or EntryDeleteFailed
     */
    public function delete(BlogEntry $entry, DateTime $time = null) {
        if (!$entry->isEntry()) {
            throw new EntryDoesNotExist();
        }

        $event_class = $entry->isArticle() ? 'Article' : 'BlogEntry';

        if ($entry->isPublished()) {
            $this->raiseEvent($entry, $event_class, 'OnDelete');
        }

        $this->deletePermalinkFile($entry);

        $this->deleteEntry($entry, $time);

        if ($entry->isPublished()) {
            $this->raiseEvent($entry, $event_class, 'DeleteComplete');
        }
    }

    public function publishReply(Reply $reply, BlogEntry $entry, \DateTime $datetime = null) {
        return $entry->addReply($reply, $datetime);
    }

    protected function getHttpClient() {
        if (!$this->http_client) {
            $this->http_client = new HttpClient();
        }
        return $this->http_client;
    }

    private function applyBlogDefaults($entry) {
        $entry->has_html = $this->blog->default_markup;
        $entry->send_pingback = $this->blog->auto_pingback;
    }

    private function getArticleDirectoryPath($entry, $basepath) {
        $dir_path = $entry->article_path;
        if (! $dir_path) {
            $dir_path = strtolower($entry->subject);
            $dir_path = preg_replace("/\s+/", "_", $dir_path);
            $dir_path = preg_replace("/\W+/", "", $dir_path);
        }
        $dir_path = Path::mk($basepath, $dir_path);
        return $dir_path;
    }

    private function createDraftsDirectory($draft_path) {
        if (! $this->fs->is_dir($draft_path)) {
            $ret = $this->wrappers->createDirectoryWrappers($draft_path, WrapperGenerator::BLOG_DRAFTS);
            if (! empty($ret)) {
                throw new CouldNotCreateDirectory('Could not create drafts directory');
            }
        }
    }

    private function getEntryDraftPath($entry, $ts, $draft_path) {
        $dirname = $entry->getPath($ts, true);
        return Path::mk($draft_path, $dirname);
    }

    private function createEntryDraftDirectory($path) {
        $ret = $this->fs->mkdir_rec($path);
        if (! $ret) {
            throw new EntryWriteFailed("Could not create directory for new draft.");
        }
        $ret = $this->wrappers->createDirectoryWrappers($path, WrapperGenerator::DRAFT_ENTRY_BASE);
    }

    private function createMonthDirectory($dir_path) {
        $month_path = dirname($dir_path);
        $year_path = dirname($month_path);
        if (! $this->fs->is_dir($year_path)) {
            $this->wrappers->createDirectoryWrappers($year_path, WrapperGenerator::YEAR_ENTRIES);
        }
        if (! $this->fs->is_dir($month_path)) {
            $this->wrappers->createDirectoryWrappers($month_path, WrapperGenerator::MONTH_ENTRIES);
        }
    }

    private function createEntryWrappers($dir_path) {
        $this->wrappers->createDirectoryWrappers($dir_path, WrapperGenerator::ENTRY_BASE);
        $this->wrappers->createDirectoryWrappers(Path::mk($dir_path, ENTRY_COMMENT_DIR), WrapperGenerator::ENTRY_COMMENTS);
        $this->wrappers->createDirectoryWrappers(Path::mk($dir_path, ENTRY_TRACKBACK_DIR), WrapperGenerator::ENTRY_TRACKBACKS);
        $this->wrappers->createDirectoryWrappers(Path::mk($dir_path, ENTRY_PINGBACK_DIR), WrapperGenerator::ENTRY_PINGBACKS);
    }

    private function updateWithHistory(BlogEntry $entry, DateTime $time) {
        $dir_path = dirname($entry->file);
        $target = Path::mk($dir_path, $entry->getPath($time->getTimestamp(), true, true).ENTRY_PATH_SUFFIX);
        $source = $entry->file;

        $this->renameEntry($source, $target);

        $ret = $this->fs->write_file($entry->file, $entry->serializeXML());
        if (! $ret) {
            $this->fs->rename($target, $source);
            throw new EntryWriteFailed();
        }
    }

    private function updateWithoutHistory(BlogEntry $entry) {
        $ret = $this->fs->write_file($entry->file, $entry->serializeXML());
        if (! $ret) {
            throw new EntryWriteFailed();
        }
    }

    private function updateEntry(BlogEntry $entry, DateTime $time) {
        if ($this->keepEditHistory()) {
            $this->updateWithHistory($entry, $time);
        } else {
            $this->updateWithoutHistory($entry);
        }
    }

    private function deleteEntry(BlogEntry $entry, DateTime $time = null) {
        if ($this->keepEditHistory()) {
            $time = $time ?: new DateTime();
            $target_file = Path::mk($entry->localpath(), $entry->getPath($time->getTimestamp(), true, true).ENTRY_PATH_SUFFIX);
            $ret = $this->fs->rename($entry->file, $target_file);
        } else {
            $ret = $this->fs->rmdir_rec($entry->localpath());
        }

        if (! $ret) {
            throw new EntryDeleteFailed();
        }
    }

    private function updateAutoPublishState($entry) {
        if ($entry->isPublished()) {
            return;
        }
        if ($entry->autopublish) {
            $this->setAutoPublish($entry);
        } else {
            $this->unsetAutoPublish($entry);
        }
    }

    private function setAutoPublish($entry) {
        $pub_task = new AutoPublishTask();
        $run_time = new DateTime($entry->autopublish_date);
        $pub_task->runAfterTime($run_time);
        $pub_task->data($entry);
        $old_task = $this->task_manager->findByKey($pub_task);
        if ($old_task) {
            $this->task_manager->remove($old_task);
        }
        $this->task_manager->add($pub_task);
    }

    private function unsetAutoPublish($entry) {
        $task = new AutoPublishTask();
        $task->data($entry);
        $pub_task = $this->task_manager->findByKey($task);
        $this->task_manager->remove($pub_task);
    }

    private function updatePermalinkFile(BlogEntry $entry) {
        if ($entry->isPublished() && ! $entry->isArticle()) {
            $subfile = $entry->calcPrettyPermalink();
            if ($subfile) {
                $subfile = Path::mk(dirname(dirname($entry->file)), $subfile);
                if (! $this->fs->file_exists($subfile)) {
                    $entry->makePrettyPermalink();
                }
            }
        }
    }

    private function deletePermalinkFile(BlogEntry $entry) {
        if (! $entry->isArticle()) {
            $subfile = $entry->permalink_name;
            $subfile = Path::mk(dirname(dirname($entry->file)), $subfile);
            if ($this->fs->file_exists($subfile) && !$this->fs->is_dir($subfile)) {
                $this->fs->delete($subfile);
            }
            $this->deleteStalePermalinkFiles($entry);
        }
    }

    private function deleteStalePermalinkFiles(BlogEntry $entry) {
        $path = dirname(dirname($entry->file));
        $base_dir = basename(dirname($entry->file));
        $listing = $this->fs->scandir($path);
        foreach ($listing as $file) {
            $full_path = Path::mk($path, $file);
            if (substr($file, -4) == ".php" && $this->fs->is_file($full_path)) {
                $content = $this->fs->read_file($full_path);
                if (strpos($content, "'$base_dir'") > 0 || strpos($content, '"'.$base_dir.'"') > 0) {
                    $this->fs->delete($full_path);
                }
            }
        }
    }

    private function renameEntry($source, $dest) {
        $ret = $this->fs->rename($source, $dest);
        if (! $ret) {
            throw new EntryRenameFailed();
        }
    }

    private function handleUploads($ent) {
        $err = array();

        $uploads = array();
        if (isset($_FILES['upload'])) {
            $uploads = FileUpload::initUploads($_FILES['upload'], $ent->localpath(), $this->fs);
        }

        foreach ($uploads as $upld) {

            $upload_error = (
                $upld->status() != FILEUPLOAD_NO_FILE &&
                $upld->status() != FILEUPLOAD_NOT_INITIALIZED
            ) || (
                $upld->status() == FILEUPLOAD_NOT_INITIALIZED &&
                ! defined("UPLOAD_IGNORE_UNINITIALIZED")
            );

            if ( $upld->completed() ) {
                $ret = $upld->moveFile();
                if (! $ret) {
                    $err[] = _('Error moving uploaded file');
                }
            } elseif ($upload_error) {
                $err[] = $upld->errorMessage();
            }
        }

        if ($err) {
            $this->raiseEvent($ent, 'BlogEntry', 'UploadError', $err);
        } elseif ($uploads) {
            # This event is raised here as sort of a hack.  The idea is that some
            # plugins will need information on uploaded files, but can only get that
            # when an event is raised by the entry.
            # In particular, this intended to regenerate the RSS2 feed after uploading
            # a file from the edit form, so that the enclosure information will be
            # set correctly.
            if (! $ent->isDraft()) {
                $ent->raiseEvent("UpdateComplete");
            }
            $ent->raiseEvent('UploadSuccess');
        }
    }

    private function updateFileForPublication($entry, $dir_path, $curr_ts, $draft_created) {
        $entry->file = Path::mk($dir_path, ENTRY_DEFAULT_FILE);
        $entry->uid = $this->user->username();
        $entry->post_ts = $curr_ts;
        $entry->setDates($curr_ts);
        $entry->ip = get_ip();
        $entry->permalink_name = $entry->calcPrettyPermalink();

        if (!$draft_created) {
            $ret = $this->handleUploads($entry);
        }

        $file_data = $entry->serializeXML();
        return $this->fs->write_file($entry->file, $file_data);
    }

    private function raiseEvent($object, $class, $event, $data = false) {
        EventRegister::instance()->activateEventFull($object, $class, $event, $data);
    }

    private function sendPingbacks($entry) {
        $client = new SocialWebClient($this->getHttpClient(), $this->fs);
        $client->sendReplies($entry);
    }
}
