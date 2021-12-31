<?php

namespace LnBlog\Storage;

use BlogEntry;
use FileNotFound;
use FS;
use Path;

class ReplyRepository
{
    private $fs;

    public function __construct(FS $fs = null) {
        $this->fs = $fs ?? NewFS();
    }

    # Method: getReplyArray
    # Get an array of replies of a particular type.  This is for internal use
    # only.  Call getComments(), getTrackbacks(), getPingbacks, or getReplies()
    # instead of this.
    #
    # Parameters:
    # entry    - The BlogEntry to get replies for
    # ext      - The file extension to scan for
    # creator  - The creator function to create the objects
    # sort_asc - Whether we should sort assending, default tur
    # altext   - Optional alternate file extension
    #
    # Returns:
    # An array of BlogComment, Trackback, or Pingback objects, depending on
    # the parameters.
    public function getReplyArray(
        BlogEntry $entry,
        string $path,
        string $ext,
        string $creator,
        bool $sort_asc = true,
        string $altext = ''
    ): array {
        $dir_path = dirname($entry->file);
        $dir_path = Path::mk($dir_path, $path);
        if (! $this->fs->is_dir($dir_path)) {
            return [];
        }
        $reply_dir = $this->fs->scan_directory($dir_path);

        $reply_files = [];
        foreach ($reply_dir as $file) {
            $cond = $this->isMatch($dir_path, $file, $ext, $altext);
            if ($cond) {
                $reply_files[] = $file;
            }
        }
        if ($sort_asc) {
            sort($reply_files);
        } else {
            rsort($reply_files);
        }

        $reply_array = [];
        foreach ($reply_files as $file) {
            $reply_array[] = new $creator(Path::mk($dir_path, $file));
        }

        return $reply_array;
    }

    # Method: getReplyCount
    # Get the number of replies of a particular type.
    #
    # Parameters:
    # entry    - The BlogEntry to get replies for
    # ext      - The file extension to scan for
    # altext   - Optional alternate file extension
    #
    # Returns:
    # An integer representing the number of replies of the given type.
    # If the call fails for some reason, then false is returned.

    public function getReplyCount(
        BlogEntry $entry,
        string $path,
        string $ext,
        string $altext = ''
    ): int {
        $dir_path = Path::get(dirname($entry->file), $path);
        $dir_array = $this->fs->scan_directory($dir_path);
        if ($dir_array === false) {
            throw new FileNotFound('Directory read failed');
        }

        $count = 0;
        foreach ($dir_array as $file) {
            $cond = $this->isMatch($dir_path, $file, $ext, $altext);
            if ($cond) {
                $count++;
            }
        }

        return $count;
    }

    private function isMatch(string $dir_path, string $file, string $ext, string $altext) {
        $cond = $this->fs->is_file(Path::mk($dir_path, $file));
        if ($cond) {
            $cond = preg_match("/[\-_\d]+".$ext."/", $file);
            if (!$cond && $altext) {
                $cond = preg_match("/[\w\d]+".$altext."/", $file);
            }
        }

        return $cond;
    }
}
