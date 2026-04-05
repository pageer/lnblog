<?php

namespace LnBlog\Tests;

use Page;

class MockPage extends Page
{
    public $body;

    public function __construct() {
        $this->body = '';
        $this->scripts = [];
    }

    public function display($page_body, $blog = false) {
        $this->body = $page_body;
    }

    public function addInlineScript($text, $type = "text/javascript"): void {
        $this->scripts[] = $text;
    }
}

