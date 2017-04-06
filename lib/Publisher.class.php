<?php
/**
 * Handles publication of blog entries, including updates and deletions.
 */
class Publisher {

    private $fs;
    private $blog;
    private $wrappers;
    private $keepHistory;

    public function __construct(Blog $blog, FS $fs, WrapperGenerator $wrappers) {
        $this->blog = $blog;
        $this->fs = $fs;
        $this->wrappers = $wrappers;
    }

    public function keepEditHistory($val = null) {
        if ($val !== null) {
            $this->keepHistory = $val;
        }
        return $this->keepHistory;
    }

    /**
     * Publish the entry as a normal blog entry.
     *
     * Puts the entry inside the normal list of blog entries.
     * Throws on failure or if the entry is already published.
     * @param   BlogEntry   $entry
     * @param   DateTime    $time
     * @throw   Exception
     */
    public function publishEntry(BlogEntry $entry, DateTime $time = null) {
        $time = $time ?: new DateTime();
        $curr_ts = $time->getTimestamp();
		
		$basepath = Path::mk($this->blog->home_path, BLOG_ENTRY_PATH);
		$dir_path = Path::mk($basepath, $entry->getPath($curr_ts));

        if (! $entry->isEntry()) {
            $this->createDraft($entry, $time);
        }
		
		# If the entry directory already exists, something is wrong. 
		if ( $this->fs->is_dir($dir_path) ) {
			$dir_path = Path::mk($basepath, $entry->getPath($curr_ts, false, true));
        }

        if ( $this->fs->is_dir($dir_path) ) {
            return false;
        }
		
		$entry->raiseEvent("OnInsert");

		# First, check that the year and month directories exist and have
		# the appropriate wrapper scripts in them.
		$month_path = dirname($dir_path);
		$year_path = dirname($month_path);
        if (! $this->fs->is_dir($year_path)) {
            $this->wrappers->createDirectoryWrappers($year_path, YEAR_ENTRIES);
        }
        if (! $this->fs->is_dir($month_path)) {
            $this->wrappers->createDirectoryWrappers($month_path, MONTH_ENTRIES);
        }
		$this->fs->rename(dirname($entry->file), $dir_path);	

		$this->wrappers->createDirectoryWrappers($dir_path, ENTRY_BASE);
		$this->wrappers->createDirectoryWrappers(Path::mk($dir_path, ENTRY_COMMENT_DIR), ENTRY_COMMENTS);
		$this->wrappers->createDirectoryWrappers(Path::mk($dir_path, ENTRY_TRACKBACK_DIR), ENTRY_TRACKBACKS);
		$this->wrappers->createDirectoryWrappers(Path::mk($dir_path, ENTRY_PINGBACK_DIR), ENTRY_PINGBACKS);
				
		$entry->file = Path::mk($dir_path, ENTRY_DEFAULT_FILE);
		$entry->post_ts = $curr_ts;
		$entry->setDates($curr_ts);
		$entry->ip = get_ip();

		//$ret = $entry->writeFileData();
		# Add a wrapper file to make the link prettier.
		//if ($ret) {
			$entry->id = $entry->globalID();
			$entry->makePrettyPermalink();
		//}
		$entry->raiseEvent("InsertComplete");
    }

    /**
     * Publish the entry as an article.
     *
     * Same as publishEntry(), but publishes the entry as an article outside 
     * of the normal blog listing structure.  Throws on failure.
     * @param   BlogEntry   $entry
     * @throw   Exception
     */
    public function publishArticle(BlogEntry $entry) {

    }

    /**
     * Unpublish the entry.
     *
     * Moves the entry from a published blog entry or article to an unpublished 
     * draft entry.  Throws on failure or if the entry is not published.
     * @param   BlogEntry   $entry
     * @throw   Exception
     */
    public function unpublish(BlogEntry $entry) {

    }

    /**
     * Save the entry as a new draft.
     *
     * Saves a new entry in the drafts folder.  Throws on failure or
     * if the entry already exists.
     * @param   BlogEntry       $entry
     * @param   DateTime|null   $time   (Optional) creation time
     * @throw   Exception
     */
    public function createDraft(BlogEntry $entry, DateTime $time = null) {
        if (! $time) {
            $time = new DateTime();
        }
		$ts = $time->getTimestamp();
        $draft_path = mkpath($this->blog->home_path, BLOG_DRAFT_PATH);
		if (! $this->fs->is_dir($draft_path)) {
			$r = $this->wrappers->createDirectoryWrappers($draft_path, BLOG_DRAFTS);
            if (! empty($r)) {
                throw new Exception('Could not create drafts directory');
            }
		}
		
        if ($entry->isEntry()) {
            throw new Exception("This draft aleady exists.");
        }
        
		$dirname = $entry->getPath($ts, true);
		$path = mkpath($draft_path, $dirname);
        $ret = $this->fs->mkdir_rec($path);
        if (! $ret) {
            throw new Exception("Could not create directory for new draft.");
        }

        $entry->file = mkpath($path, ENTRY_DEFAULT_FILE);
        $entry->date = '';
        $entry->timestamp = '';
		$entry->setDates($ts);
		
		$file_data = $entry->serializeXML();
        $ret = $this->fs->write_file($entry->file, $file_data);

        if (! $ret) {
            throw new Exception("Failed to write draft entry data.");
        }
    }

    /**
     * Update an existing entry on disk.
     *
     * Writes out the state of the entry without changing its publication status.
     * Throws if the update fails or the entry does not already exist.
     * @param   BlogEntry   $entry
     * @param   DateTime    $time
     * @throws   EntryDoesNotExist|EntryWriteFailed|EntryRenameFailed
     */
    public function update(BlogEntry $entry, DateTime $time = null) {
        $time = $time ?: new DateTime();

        if (! $this->fs->file_exists($entry->file)) {
            throw new EntryDoesNotExist();
        }

        $event_class = $entry->isArticle() ? 'Article' : 'BlogEntry';

        if ($entry->isPublished()) {
            EventRegister::instance()->activateEventFull($entry, $event_class, 'OnUpdate');
        }
		
		$dir_path = dirname($entry->file);
		$entry->ip = get_ip();
		$entry->date = fmtdate(ENTRY_DATE_FORMAT, $time->getTimestamp());
		$entry->timestamp = $time->getTimestamp();


        if ($this->keepEditHistory()) {
            $this->updateWithHistory($entry, $time);
        } else {
            $this->updateWithoutHistory($entry);
        }

        if ($entry->isPublished() && ! $entry->isArticle()) {    
            $this->updatePermalinkFile($entry);
        }

        if ($entry->isPublished()){
            EventRegister::instance()->activateEventFull($entry, $event_class, 'UpdateComplete');
        }
    }

    private function updateWithHistory(BlogEntry $entry, DateTime $time) {
		$dir_path = dirname($entry->file);
		$target = Path::mk($dir_path, $entry->getPath($time->getTimestamp(), true, true).ENTRY_PATH_SUFFIX);
		$source = $entry->file;

        $ret = $this->fs->rename($source, $target);
        if (! $ret) {
            throw new EntryRenameFailed();
        }

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

    private function updatePermalinkFile(BlogEntry $entry) {
        $subfile = $entry->calcPrettyPermalink();
        if ($subfile) {
            $subfile = Path::mk(dirname(dirname($entry->file)), $subfile);
            if (! $this->fs->file_exists($subfile)) {
                $entry->makePrettyPermalink();
            }
        }
    }

    /**
     * Completely delete an entry.
     *
     * Applies to both published and draft entries.  Throws on failure of if 
     * the entry has not been saved.
     * @param   BlogEntry   $entry
     * @param   DateTime    $time
     * @throw   Exception
     */
    public function delete(BlogEntry $entry, DateTime $time = null) {
        if (!$entry->isEntry()) {
            throw new EntryDoesNotExist();
        }
		
        $event_class = $entry->isArticle() ? 'Article' : 'BlogEntry';

        if ($entry->isPublished()) {
            EventRegister::instance()->activateEventFull($entry, $event_class, 'OnDelete');
        }

		$subfile = $entry->calcPrettyPermalink();
        $subfile = Path::mk(dirname($entry->localpath()), $subfile);
		if ($this->fs->file_exists($subfile)) {
			$this->fs->delete($subfile);
		}
		
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
		
        if ($entry->isPublished()){
            EventRegister::instance()->activateEventFull($entry, $event_class, 'DeleteComplete');
        }
    }

    /**
     * Add or update file attachments for the entry.
     *
     * Adds files attachments to the entry.  Any filenames that already exist
     * will be overwritten.  Throws if one or more fails or entry is not saved.
     * @param   BlogEntry   $entry
     * @param   array       $files_data The PHP $_FILES array of attachments.
     * @throw   Exception
     */
    public function addAttachments(BlogEntry $entry, array $files) {

    }
    
}
