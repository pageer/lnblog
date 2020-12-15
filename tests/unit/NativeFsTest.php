<?php

class NativeFsTest extends PHPUnit\Framework\TestCase
{
    
    function testCreator() {
        $fs = new NativeFS();
        $this->assertEquals($fs->directory_mode, 0000);
        $this->assertEquals($fs->default_mode, 0000);
        $this->assertEquals($fs->script_mode, 0000);
    }
    
    function testLocalpathToFSPath() {
        $fs = new NativeFS();
        $this->assertEquals($fs->localpathToFSPath('/home/foo'), '/home/foo');
    }
    
    function testFSPathToLocalpath() {
        $fs = new NativeFS();
        $this->assertEquals($fs->FSPathToLocalpath('/home/foo'), '/home/foo');
    }
    
    function testIsScript() {
        $fs = new NativeFS();
        $this->assertTrue($fs->isScript("temp/foo.php"));
        $this->assertFalse($fs->isScript("temp/bar.txt"));
        $this->assertFalse($fs->isScript("temp/bar"));
    }
}
