<?php

namespace LnBlog\Model;

class GlobalId
{
    public string $class;
    public string $blogId;
    public string $entryId;
    public string $replyId;

    public function __construct(
        string $class = "",
        string $blogId = "",
        string $entryId = "",
        string $replyId = ""
    ) {
        $this->class = $class;
        $this->blogId = $blogId;
        $this->entryId = $entryId;
        $this->replyId = $replyId;
    }
}
