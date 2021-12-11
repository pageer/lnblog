<?php

namespace LnBlog\Storage;

use Blog;
use BlogEntry;
use FS;
use Path;

class EntryRepository
{
    public $has_more_entries = false;

    private $blog;
    private $fs;

    private $entrylist = [];

    public function __construct(Blog $blog, FS $fs = null) {
        $this->blog = $blog;
        $this->fs = $fs ?? NewFS();
    }

    public function getAll(): array {
        # This should theoretically be slightly faster when we actually want everything.
        return $this->getEntriesByGlob();
    }

    public function getLimit(int $number = -1, int $offset = 0): array {
        return $this->getEntriesRecursive($number, $offset);
    }

    /*
    Method: getArticles
    Returns a list of all articles, in no particular order.

    Parameters:
    number      - *Optional* number of articles to return.  Default is all.

    Returns:
    An array of Article objects.
    */
    public function getArticles($number = null): array {
        $art = new BlogEntry('', $this->fs);
        $art_path = Path::get($this->blog->home_path, BLOG_ARTICLE_PATH);
        $art_list = $this->fs->scan_directory($art_path);
        $ret = [];
        $count = 0;
        foreach ($art_list as $dir) {
            $path = Path::mk($art_path, $dir);
            if (BlogEntry::pathIsArticle($path, $this->fs)) {
                $ent = NewEntry($path);
                $ent->parent = $this->blog;
                $ret[] = $ent;
                $count++;
            }
            if ($number && $count >= $number) {
                break;
            }
        }
        return $ret;
    }
    /*
    Method: getDrafts
    Gets all the current drafts for this blog.

    Returns:
    An array of BlogEntry objects.
    */
    public function getDrafts() {
        $art = NewBlogEntry();
        $art_path = Path::mk($this->blog->home_path, BLOG_DRAFT_PATH);

        $art_list = $this->fs->scan_directory($art_path);
        $ret = array();
        foreach ($art_list as $dir) {
            if ($art->isEntry(Path::mk($art_path, $dir)) ) {
                $ent = NewEntry(Path::mk($art_path, $dir));
                $ent->parent = $this->blog;
                $ret[] = $ent;
            }
        }
        $sort_by_date = function ($e1, $e2) {
            return $e1->post_ts <=> $e2->post_ts;
        };
        usort($ret, $sort_by_date);
        return $ret;
    }

    private function getEntriesRecursive(int $number = -1, int $offset = 0): array {
        $entry = NewBlogEntry();
        $this->entrylist = array();
        if ($number == 0) {
            return [];
        }

        $ent_dir = Path::mk($this->blog->home_path, BLOG_ENTRY_PATH);
        $num_scanned = 0;
        $num_found = 0;

        $this->has_more_entries = false;

        $year_list = $this->fs->scan_directory($ent_dir, true);
        rsort($year_list);

        foreach ($year_list as $year) {
            $month_list = $this->fs->scan_directory(Path::mk($ent_dir, $year), true);
            rsort($month_list);
            foreach ($month_list as $month) {
                $path = Path::mk($ent_dir, $year, $month);
                $ents = $this->fs->scan_directory($path, true);
                rsort($ents);
                foreach ($ents as $e) {
                    $ent_path = Path::mk($path, $e);
                    if ( $entry->isEntry($ent_path) ) {
                        if ($num_scanned >= $offset) {
                            $num_found++;
                            # If we've hit the max, then break out of all 3 loops.
                            if ($num_found > $number && $number >= 0) {
                                $this->has_more_entries = true;
                                break 3;
                            }
                            $ent = NewBlogEntry($ent_path);
                            $ent->parent = $this->blog;
                            $this->entrylist[] = $ent;
                        }
                        $num_scanned++;
                    }
                }  # End month loop
            }  # End year loop
        }  # End archive loop
        return $this->entrylist;
    }

    private function getEntriesByGlob(int $number = -1, int $offset = 0): array {
        if ($number == 0) {
            return [];
        }

        $this->entrylist = [];
        $num_scanned = 0;
        $num_found = 0;

        # This glob should find all of the entry.xml files, which are required
        # for valid entries, in a single step.
        $entry_file_glob = Path::mk($this->blog->home_path, BLOG_ENTRY_PATH, '*', '*', '*', ENTRY_DEFAULT_FILE);
        $files = $this->fs->glob($entry_file_glob);

        if ($number > 0) {
            $offset = -1 * ($offset + $number);
            $files2 = array_slice($files, $offset, $number);
        }

        $entry_list = array_reverse(isset($files2) ? $files2 : $files);

        $instantiator = function ($entry_path) {
            $ent = NewBlogEntry(dirname($entry_path));
            $ent->parent = $this->blog;
            return $ent;
        };
        
        $this->entrylist = array_map($instantiator, $entry_list);
        $this->has_more_entries = count($entry_list) - $offset - $number > 0;
        return $this->entrylist;
    }
}
