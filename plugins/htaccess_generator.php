<?php

# Plugin: HTAccessGenerator
# Creates Apache .htaccess files in blog root directories.
#
# When blogs are created or updated, this plugin creates a .htaccess file
# with rewrite rules to redirect attempts to directly access data files or 
# other incorrect URLs.

class HTAccessGenerator extends Plugin
{
    public $copy_parent;

    const LNBLOG_START_MARKER = '# START LnBlog section';
    const LNBLOG_END_MARKER = '# END LnBlog section';

    private $fs;

    public function __construct(FS $fs = null) {
        $this->plugin_desc = _("Adds an .htaccess file to blogs that blocks access to system-managed files.  Enabling this globally is recommended if you're running Apache.");
        $this->plugin_version = "1.0.0";

        # Useful for sub-directories because the child file is the only one used.
        $this->addOption("copy_parent", _("Copy of parent directory's .htaccess (if current does not have one)."), false, "checkbox");

        parent::__construct();
        $this->fs = $fs ?: NewFS();

        $this->registerEventHandler("blog", "UpgradeComplete", "create_file");
        $this->registerEventHandler("blog", "InsertComplete", "create_file");
        $this->registerEventHandler("blog", "OnInit", "updateManagedFiles");
    }

    # Method: updateManagedFiles
    # Tells the blog that the .htaccess file is an internally managed file, not an attachment.
    public function updateManagedFiles($blog) {
        $blog->addManagedFile('.htaccess');
    }

    # Method: create_file
    # Create a .htaccess file for the blog
    public function create_file($blog) {
        # Include the .htaccess file from the document root, if there is one.
        # We do this because .htaccess files in lower directories completely
        # over-ride ones in higher directories.
        $base_path = $blog->home_path;
        $htaccess_path = Path::mk($base_path, '.htaccess');
        $head_lines = [];
        $lines = [];
        $tail_lines = [];

        $parent_file = Path::mk(dirname($base_path), '.htaccess');
        $use_parent_file = 
            $this->copy_parent &&
            $this->fs->file_exists($parent_file) &&
            !$this->fs->file_exists($htaccess_path);
        $file_to_copy = $use_parent_file ? $parent_file : $htaccess_path;

        if ($this->fs->file_exists($file_to_copy)) {
            $existing_lines = $this->fs->file($file_to_copy);

            # Filter out anything in the LnBlog section, if present.
            $found_lnblog_start = false;
            $found_lnblog_end = false;
            foreach ($existing_lines as $line) {
                if (strpos($line, self::LNBLOG_START_MARKER) === 0) {
                    $found_lnblog_start = true;
                };
                if (!$found_lnblog_start && !$found_lnblog_end) {
                    $head_lines[] = $line;
                } elseif ($found_lnblog_start && $found_lnblog_end) {
                    $tail_lines[] = $line;
                } elseif ($found_lnblog_start && !$found_lnblog_end) {
                    # In the LnBlog section - skip the line
                } else { # Found end but not start
                    # This should not happen - the file is probably corrupt.
                    # Just stick the line in the head.
                    $head_lines[] = $line;
                }
                # Check this last so we do the right thing with the marker.
                if (strpos($line, self::LNBLOG_END_MARKER) === 0) {
                    $found_lnblog_end = true;
                };
            }
        }

        $filter_php = function ($item) {
            return substr($item, -4) !== '.php';
        };
        $blocked_files = array_filter($blog->getManagedFiles(), $filter_php);
        array_unshift($blocked_files, '.htaccess');

        $lines = array_merge(
            $head_lines,
            $this->getLnBlogBlockContent($blocked_files),
            $tail_lines
        );

        $data = implode("\n", $lines);
        $this->fs->write_file($htaccess_path, $data);
    }

    private function getLnBlogBlockContent($blocked_files) {
        $escape_metachars = function ($item) {
            return str_replace('.', '\\.', $item);
        };
        $blocked_files = array_map($escape_metachars, $blocked_files);
        $blocked_file_regex = implode('|', $blocked_files);

        $entry_base = 'entries/\d{4}/\d{2}/\d{2}_\d{4,6}';
        $content_base = 'content/.+';

        $lines[] = self::LNBLOG_START_MARKER . ' - ' . $this->plugin_version;
        $lines[] = '# This section managed by LnBlog HtAccessGenerator';
        $lines[] = 'RewriteEngine On';
        $lines[] = 'Options +FollowSymlinks';
        $lines[] = '';
        $lines[] = '<FilesMatch "^' . $blocked_file_regex . '$">';
        $lines[] = '    Order Allow,Deny';
        $lines[] = '    Deny from all';
        $lines[] = '</FilesMatch>';
        $lines[] = '';
        $lines[] = "RewriteRule ^($entry_base/comments/).+\.xml$ $1 [nc]";
        $lines[] = "RewriteRule ^($entry_base/pingback/).+\.xml$ $1 [nc]";
        $lines[] = "RewriteRule ^($entry_base/trackback/).+\.xml$ $1 [nc]";
        $lines[] = "RewriteRule ^($entry_base/)" . ENTRY_DEFAULT_FILE . "$ $1 [nc]";
        $lines[] = "RewriteRule ^($entry_base/comments/)" . COMMENT_DELETED_PATH . ".*$ $1 [nc]";
        $lines[] = "RewriteRule ^($content_base/comments/).+\.xml$ $1 [nc]";
        $lines[] = "RewriteRule ^($content_base/pingback/).+\.xml$ $1 [nc]";
        $lines[] = "RewriteRule ^($content_base/trackback/).+\.xml$ $1 [nc]";
        $lines[] = "RewriteRule ^($content_base/)" . ENTRY_DEFAULT_FILE . "$ $1 [nc]";
        $lines[] = "RewriteRule ^($content_base/comments/)" . COMMENT_DELETED_PATH . ".*$ $1 [nc]";
        $lines[] = self::LNBLOG_END_MARKER; 

        return $lines;
    }
}

# NOTE: Currently this does not work when enabled on a per-blog basis.
# It has to be enabled globally.
$gen = new HTAccessGenerator();
