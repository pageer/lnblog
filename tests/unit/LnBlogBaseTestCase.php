<?php

namespace LnBlog\Tests;

class LnBlogBaseTestCase extends \PHPUnit\Framework\TestCase
{
    protected $prophet;

    protected function setUp(): void {
        $this->prophet = new \Prophecy\Prophet();
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }
}
