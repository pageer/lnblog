<?php

class BlogTest extends PHPUnit\Framework\TestCase
{
    private $prophet;
    private $blog;
    private $fs;
    
    public function testAutoPingbackEnabled_WhenSettingIsAll_ReturnTrue() {
        $this->blog->auto_pingback = 'all';

        $enabled = $this->blog->autoPingbackEnabled();

        $this->assertTrue($enabled);
    }
    
    public function testAutoPingbackEnabled_WhenSettingIsNew_ReturnTrue() {
        $this->blog->auto_pingback = 'new';

        $enabled = $this->blog->autoPingbackEnabled();

        $this->assertTrue($enabled);
    }
    
    public function testAutoPingbackEnabled_WhenSettingIsNone_ReturnFalse() {
        $this->blog->auto_pingback = 'none';

        $enabled = $this->blog->autoPingbackEnabled();

        $this->assertFalse($enabled);
    }
    
    public function testAutoPingbackEnabled_WhenSettingIsOne_ReturnTrue() {
        $this->blog->auto_pingback = '1';

        $enabled = $this->blog->autoPingbackEnabled();

        $this->assertTrue($enabled);
    }
    
    public function testAutoPingbackEnabled_WhenSettingIsZero_ReturnFalse() {
        $this->blog->auto_pingback = '0';

        $enabled = $this->blog->autoPingbackEnabled();

        $this->assertFalse($enabled);
    }
    
    public function setUp(): void {
        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize('FS');

        $this->blog = new Blog("", $this->fs->reveal());
    }

    protected function tearDown(): void {
        Path::$sep = DIRECTORY_SEPARATOR;
        $this->prophet->checkPredictions();
    }


}

