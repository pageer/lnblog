<?php

namespace LnBlog\Storage;

use Blog;
use SaveFailure;
use SystemConfig;

class BlogRepository
{
    public function getAll(): array {
        $paths = SystemConfig::instance()->blogRegistry();
        $ret = [];
        foreach ($paths as $urlpath) {
            $ret[] = new Blog($urlpath->path());
        }
        return $ret;
    }

    public function exists(Blog $blog): bool {
        return $blog->isBlog();
    }

    public function save(Blog $blog) {
        if ($this->exists($blog)) {
            $ret = $blog->update();
        } else {
            $ret = $blog->insert();
        }

        if (!$ret) {
            throw new SaveFailure(_('Could not save blog'));
        }
    }
}
