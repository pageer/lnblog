<?php

namespace LnBlog\Tests\Export;

use FileWriteFailed;
use FS;
use LnBlog\Export\ExportTarget;
use LnBlog\Tests\LnBlogBaseTestCase;

class ExportTargetTest extends LnBlogBaseTestCase
{
    private $fs;

    public function testSave_Success(): void {
        $file = '/path/to/whatever';
        $this->fs
             ->write_file($file, 'test123')
             ->willReturn(7)
             ->shouldBeCalled();

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportFile($file);
        $target->setContent('test123');
        $target->save();
    }

    public function testSave_Failure(): void {
        $file = '/path/to/whatever';
        $this->fs
             ->write_file($file, 'test123')
             ->willReturn(false)
             ->shouldBeCalled();

        $this->expectException(FileWriteFailed::class);
        $this->expectExceptionMessage("Failed to write $file");

        $target = new ExportTarget($this->fs->reveal());
        $target->setExportFile($file);
        $target->setContent('test123');
        $target->save();
    }

    protected function setUp(): void {
        parent::setUp();
        $this->fs = $this->prophet->prophesize(FS::class);
    }
}
