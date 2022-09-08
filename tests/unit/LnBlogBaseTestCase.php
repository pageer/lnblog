<?php

namespace LnBlog\Tests;

use Path;

class LnBlogBaseTestCase extends \PHPUnit\Framework\TestCase
{
    protected $prophet;

    protected function setUp(): void {
        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }
}
