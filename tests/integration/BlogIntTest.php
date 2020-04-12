<?php

class BlogIntTest extends \PHPUnit\Framework\TestCase {

    function testInsertDelete() {

        $currdir = getcwd();

        $b = new Blog();
        $b->name = "Some blog";
        $b->description = "A random test blog.";
        $ret = $b->insert("test_temp/testblog");

        $this->assertTrue($ret, "Insert returned false");
        $this->assertEquals($currdir, getcwd(), "Working directory has changed");
        $this->assertTrue(is_dir("test_temp/testblog"), "test_temp/testblog is not a directory");
        $this->assertTrue(is_dir("test_temp/testblog/entries"), "test_temp/testblog/entries is not a directory");
        $this->assertTrue(is_dir("test_temp/testblog/feeds"), "test_temp/testblog/feeds is not a directory");
        $this->assertTrue(is_file("test_temp/testblog/blogdata.ini"), "test_temp/testblog/blogdata.ini is not a file");

        $ret = $b->delete();
        $this->assertTrue($ret);
        $this->assertFalse(is_dir("test_temp/testblog"), "test_temp/testblog still exists");

    }

    function testCreatorPathDetection() {

        $b = new Blog();
        $b->name = "Some blog";
        $b->description = "A random test blog.";
        $ret = $b->insert("test_temp/testblog");

        $blog2 = new Blog("test_temp/testblog");

        $this->assertEquals(realpath("test_temp/testblog"), $blog2->home_path);

        $b->delete();

    }

    protected function setUp(): void {
        $this->removeTempDir();
    }

    protected function tearDown(): void {
        $this->removeTempDir();
    }

    protected function removeTempDir() {
        $fs = new NativeFS();
        if ($fs->is_dir("test_temp")) {
            $fs->rmdir_rec("test_temp");
        }
        if ($fs->is_dir("feeds")) {
            $fs->rmdir_rec("feeds");
        }
    }
}
