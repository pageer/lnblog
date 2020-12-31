<?php

class UrlResolver
{

    private $config;
    private $fs;

    public function __construct(SystemConfig $config = null, FS $fs = null) {
        $this->config = $config ?: SystemConfig::instance();
        $this->fs = $fs ?: new NativeFS();
    }

    # Method: localpathToUri
    # Convert a local path to a URI in either the install root, userdata directory, or selected blog.
    #
    # If a blog or entry is specified, then these will be used first when trying to resolve the 
    # URI of a relative file path.  That is, if you pass a relative path, the entry will be checked
    # first, then the blog, then userdata and install root.
    #
    # Parameters:
    # path  - The path for which to get the URI
    # blog  - The blog in which to search
    # entry - The entry in which to search
    #
    # Returns:
    # String containing the URI or the unmodified path if it could not be converted.
    public function localpathToUri($path, Blog $blog = null, BlogEntry $entry = null) {
        $path_obj = new Path();
        $file = '';
        $roots = [];
        $is_absolute = $path_obj->isAbsolute($path);
        if ($is_absolute) {
            $file = $this->fs->realpath($path);
            if ($file && substr($path, -1) == Path::$sep & substr($file, -1) != Path::$sep) {
                $file .= Path::$sep;
            }
        }
        $registry = $this->config->blogRegistry();

        if ($entry) {
            $blog_root = $registry[$entry->getParent()->blogid] ?? new UrlPath('', '');
            $rel_path = str_replace($blog_root->path(), '', $entry->localpath());
            $entry_url = $blog_root->url() . $this->urlSlashes(ltrim($rel_path, Path::$sep)) . '/';
            $entry_url_path = new UrlPath($entry->localpath(), $entry_url);
            $roots[] = $entry_url_path;
        }
        if ($blog && isset($registry[$blog->blogid])) {
            $roots[] = $registry[$blog->blogid];
        }
        $roots[] = $this->config->userData();
        $roots[] = $this->config->installRoot();
        
        foreach ($roots as $root) {
            if ($is_absolute) {
                $real_root = $this->fs->realpath($root->path());
                if ($file && strpos($file, $real_root) === 0) {
                    $rel_path = str_replace($real_root, '', $file);
                    $url = $root->url() . $this->urlSlashes(ltrim($rel_path, Path::$sep));
                    return $url;
                }
            } else {
                $file = Path::mk($root->path(), $path);
                if ($this->fs->file_exists($file)) {
                    return $root->url() . $this->urlSlashes($path);
                }
            }
        }
        return $path;
    }

    # Method: absoluteLocalpathToUri
    # Convert an absolute path to a URI.  This will search for the file in all
    # registered blogs as well as userdata and install root
    #
    # Parameters:
    # path - The path to convert ot a URI
    #
    # Returns:
    # A string containing the URI or the unmodified path if resolution failed
    public function absoluteLocalpathToUri($path) {
        $path_obj = new Path();
        $roots = $this->config->blogRegistry();
        $roots[] = $this->config->userData();
        $roots[] = $this->config->installRoot();

        if (!$path_obj->isAbsolute($path)) {
            throw new Exception("Path is not absolute ($path)");
        }

        $file = $this->fs->realpath($path);

        foreach ($roots as $root) {
            $real_root = $this->fs->realpath($root->path());
            if (strpos($file, $real_root) === 0) {
                $rel_path = str_replace($real_root, '', $file);
                $url = $root->url() . ltrim($rel_path, Path::$sep);
                return $url;
            }
        }
        return $path;
    }

    # Method: uriToLocalpath
    # Convert a URI into a local path, based on the registered blogs, userdata, 
    # and install root directories
    #
    # Parameters:
    # uri  - The URI string to convert to a local path
    # blog - The optional blog
    #
    # Returns:
    # String containing the local path or the unmodified URI if resolution failed
    public function uriToLocalpath($uri, Blog $blog = null) {
        $roots = [
            $this->config->userData(),
            $this->config->installRoot(),
        ];
        $blog_registry = $this->config->blogRegistry();
        if ($blog && $blog_registry[$blog->blogid]) {
            array_unshift($roots, $blog_registry[$blog->blogid]);
        } else {
            $roots = array_merge($roots, $blog_registry);
        }
        
        foreach ($roots as $root) {
            if (strpos($uri, $root->url()) === 0) {
                $path = str_replace($root->url(), '', $uri);
                return Path::mk($root->path(), $path);
            }
        }
        return $uri;
    }

    public function generateRoute($action, LnBlogObject $entity = null, array $params = []) {
        if ($entity instanceof Blog) {
            return $this->getBlogRoute($action, $entity, $params);
        } elseif ($entity instanceof BlogEntry) {
            return $this->getEntryRoute($action, $entity, $params);
        } elseif ($entity instanceof BlogComment) {
            return $this->getCommentRoute($action, $entity, $params);
        } elseif ($entity instanceof Trackback) {
            return $this->getTrackbackRoute($action, $entity, $params);
        }
    }

    private function getBlogRoute($action, Blog $blog, $params) {
        $registry = $this->config->blogRegistry();
        $blog_root = '';
        if (isset($registry[$blog->blogid])) {
            $blog_root = $registry[$blog->blogid]->url();
        }
        switch ($action) {
            case 'permalink':
            case 'base':
            case 'blog':
            case 'page':
                return $blog_root;
            case 'articles':
                return $blog_root . BLOG_ARTICLE_PATH . '/';
            case 'entries':
            case 'archives':
                return $blog_root . BLOG_ENTRY_PATH . '/';
            case 'drafts':
            case 'listdrafts':
                return $blog_root . BLOG_DRAFT_PATH . '/';
            case 'year':
            case 'listyear':
                return $blog_root . BLOG_ENTRY_PATH . sprintf('/%04d/', $params['year']);
            case 'month':
            case 'listmonth':
                return $blog_root . BLOG_ENTRY_PATH . sprintf('/%04d/%02d/', $params['year'], $params['month']);
            case 'day':
            case 'showday':
                return $blog_root . BLOG_ENTRY_PATH . sprintf('/%04d/%02d/?day=%02d', $params['year'], $params['month'], $params['day']);
            case 'listall':
                return $blog_root . BLOG_ENTRY_PATH . '/?list=yes';
            case 'addentry':
                return $blog_root . '?action=newentry';
            case 'addarticle':
                return $blog_root . '?action=newentry&type=article';
            case 'delentry':
                return $blog_root . '?action=delentry&entry=' . $params['entryid'];
            case 'upload':
                $query = empty($params['profile']) ? '' : '&profile=' . $params['profile'];
                return $blog_root . '?action=upload' . $query;
            case 'scaleimage':
                $query = empty($params['profile']) ? '' : '&profile=' . $params['profile'];
                return $blog_root . '?action=scaleimage' . $query;
            case 'edit':
                return $blog_root . '?action=edit';
            case 'manage_reply':
            case 'manage_all':
                return $blog_root . '?action=managereply';
            case 'manage_year':
                return $blog_root . '?action=managereply&year=' . $params['year'];
            case 'manage_month':
                $query = $this->validateParams($params, ['year', 'month']);
                return $blog_root . sprintf('?action=managereply&year=%04d&month=%02d', $params['year'], $params['month']);
            case 'login':
                return $blog_root . '?action=login';
            case 'logout':
                return $blog_root . '?action=logout';
            case 'editfile':
                $this->validateParams($params, ['file']);
                $query = $this->combineParams($params, ['file', 'profile', 'map', 'list', 'richedit', 'target']);
                return $blog_root . '?action=editfile' . ($query ? "&$query" : '');
            case 'edituser':
                return $blog_root . '?action=useredit';
            case 'pluginconfig':
                return $blog_root . '?action=plugins';
            case 'pluginload':
                return $blog_root . '?action=pluginload';
            case 'tags':
                $tag_part = $this->combineParams($params, ['tag']);
                return $blog_root . '?action=tags' . ($tag_part ? "&$tag_part" : '');
            case 'script':
                return $blog_root . '?script=' . $params['script'];
            case 'plugin':
                $this->validateParams($params, ['plugin']);
                $parts = [];
                foreach ($params as $key => $value) {
                    $parts[] = "$key=$value";
                }
                return $blog_root . '?' . implode('&', $parts);
        }
    }

    private function getEntryRoute($action, BlogEntry $entry, $params) {
        $base_dir = dirname($entry->file);
        $sep = Path::$sep;
        switch ($action) {
            case 'permalink':
            case 'entry':
            case 'page':
                $permalink_name = $entry->permalink_name ?: $entry->calcLegacyPermalink();
                $permalink_file = Path::mk(dirname($base_dir), $permalink_name);
                if ($permalink_name && $this->fs->file_exists($permalink_file)) {
                    return $this->localpathToUri($permalink_file, $entry->getParent());
                }
                return $this->localpathToUri($base_dir . $sep, $entry->getParent());
            case 'base':
                return $this->localpathToUri($base_dir . $sep, $entry->getParent());
            case 'basepage':
                return $this->localpathToUri($base_dir . $sep . 'index.php', $entry->getParent());
            case 'comment':
                $url = sprintf("%s$sep%s$sep", $base_dir, ENTRY_COMMENT_DIR);
                return $this->localpathToUri($url, $entry->getParent());
            case 'commentpage':
                $url = sprintf("%s$sep%s{$sep}index.php", $base_dir, ENTRY_COMMENT_DIR);
                return $this->localpathToUri($url, $entry->getParent());
            case 'send_tb':
                $url = sprintf("%s$sep%s$sep?action=ping", $base_dir, ENTRY_TRACKBACK_DIR);
                return $this->localpathToUri($url, $entry->getParent());
            case 'get_tb':
                $url = sprintf("%s$sep%s{$sep}index.php", $base_dir, ENTRY_TRACKBACK_DIR);
                return $this->localpathToUri($url, $entry->getParent());
            case 'trackback':
                $url = sprintf("%s$sep%s$sep", $base_dir, ENTRY_TRACKBACK_DIR);
                return $this->localpathToUri($url, $entry->getParent());
            case 'pingback':
                $url = sprintf("%s$sep%s$sep", $base_dir, ENTRY_PINGBACK_DIR);
                return $this->localpathToUri($url, $entry->getParent());
            case 'upload':
                return $this->localpathToUri($base_dir, $entry->getParent()) . '/?action=upload';
            case 'scaleimage':
                return $this->localpathToUri($base_dir, $entry->getParent()) . '/?action=scaleimage';
            case 'edit':
                return $this->localpathToUri($base_dir, $entry->getParent()) . '/?action=editentry';
            case 'editDraft':
                return $this->localpathToUri($base_dir, $entry->getParent()) . '/';
            case 'delete':
                $type = 'entry';
                if ($entry->isDraft()) {
                    $type = 'draft';
                } elseif ($entry->isArticle()) {
                    $type = 'article';
                }
                return sprintf(
                    '%s?action=delentry&%s=%s',
                    $this->getBlogRoute('base', $entry->getParent(), []),
                    $type,
                    $entry->entryID()
                );
            case 'manage_reply':
            case 'managereply':
                return $this->localpathToUri($base_dir, $entry->getParent()) . '/?action=managereplies';
        }
    }

    private function getCommentRoute($action, BlogComment $comment, $params) {
        $base_dir = dirname($comment->file);
        $parent = $comment->getParent();
        $blog = $parent->getParent();
        switch ($action) {
            case 'permalink':
            case 'comment':
                return sprintf(
                    '%s/#%s',
                    $this->localpathToUri($base_dir, $blog),
                    $comment->getAnchor()
                );
            case 'delete':
                $type = $parent->isArticle() ? 'article' : 'entry';
                return sprintf(
                    '%s?action=delcomment&%s=%s&delete=%s',
                    $this->getBlogRoute('base', $blog, []),
                    $type,
                    $parent->entryID(),
                    $comment->getAnchor()
                );
        }
    }

    private function getTrackbackRoute($action, Trackback $trackback, $params) {
        $base_dir = dirname($trackback->file);
        $parent = $trackback->getParent();
        $blog = $parent->getParent();
        switch ($action) {
            case 'trackback':
            case 'pingback':
            case 'permalink':
                return sprintf(
                    '%s/#%s',
                    $this->localpathToUri($base_dir, $blog),
                    $trackback->getAnchor()
                );
            case 'delete':
                $type = $parent->isArticle() ? 'article' : 'entry';
                return sprintf(
                    '%s?action=delcomment&%s=%s&delete=%s',
                    $this->getBlogRoute('base', $blog, []),
                    $type,
                    $parent->entryID(),
                    $trackback->getAnchor()
                );
        }
    }

    private function validateParams($params, $required_params) {
        foreach ($required_params as $req) {
            if (!isset($params[$req])) {
                throw new Exception('Missing required parameter');
            }
        }
    }

    private function combineParams($params, $valid_params) {
        $query_params = [];

        foreach ($valid_params as $param) {
            if (isset($params[$param])) {
                $query_params[] = "$param=" . $params[$param];
            }
        }

        return implode('&', $query_params);
    }

    private function urlSlashes($path) {
        if (DIRECTORY_SEPARATOR === '/') {
            return $path;
        }
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }
}
