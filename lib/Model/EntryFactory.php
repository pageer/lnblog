<?php

namespace LnBlog\Model;

use \BlogComment;
use \EntryNotFound;
use \Pingback;
use \Trackback;

class EntryFactory
{
    public function __construct() {

    }

    public function getReply(string $globalID): Reply {
        if (strpos($globalID, '#comment') !== false) {
            return new BlogComment($globalID);
        } elseif (strpos($globalID, '#trackback') !== false) {
            return new Trackback($globalID);
        } elseif (strpos($globalID, '#pingback') !== false) {
            return new Pingback($globalID);
        }
        throw new EntryNotFound(spf_("Reply ID %s does not exist", $globalID));
    }
}
