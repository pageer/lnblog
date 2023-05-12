<?php

namespace LnBlog\Tests;

use Path;
use Prophecy\Prophet;

abstract class LnBlogBaseTestCase extends \PHPUnit\Framework\TestCase
{
    protected $prophet;

    protected function setUp(): void {
        Path::$sep = '/';
        $this->prophet = new Prophet();
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }
}
