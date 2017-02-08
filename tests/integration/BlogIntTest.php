<?php

class BlogIntTest extends PHPUnit_Framework_TestCase {
    
   	function testInsertDelete() {
	
		$currdir = getcwd();
	
		$b = new Blog();
		$b->name = "Some blog";
		$b->description = "A random test blog.";
		$ret = $b->insert("temp/testblog");

		$this->assertTrue($ret, "Insert returned false");
		$this->assertEquals($currdir, getcwd(), "Working directory has changed");
		$this->assertTrue(is_dir("temp/testblog"), "temp/testblog is not a directory");
		$this->assertTrue(is_dir("temp/testblog/entries"), "temp/testblog/entries is not a directory");
		$this->assertTrue(is_dir("temp/testblog/feeds"), "temp/testblog/feeds is not a directory");
		$this->assertTrue(is_file("temp/testblog/blogdata.ini"), "temp/testblog/blogdata.ini is not a file");
		
		$ret = $b->delete();
		$this->assertTrue($ret);
		$this->assertFalse(is_dir("temp/testblog"), "temp/testblog still exists");
		
	}
    
    function testCreatorPathDetection() {
	
		$b = new Blog();
		$b->name = "Some blog";
		$b->description = "A random test blog.";
		$ret = $b->insert("temp/testblog");
		
		$blog2 = new Blog("temp/testblog");
		
		$this->assertEquals(realpath("temp/testblog"), $blog2->home_path);
		
		$b->delete();
	
	}
 
}