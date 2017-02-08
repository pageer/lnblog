<?php
class BlogEntryIntTest extends PHPUnit_Framework_TestCase {
    
    function setUp() {
		$this->blog = new Blog();
		$this->blog->name = "foo";
		$this->blog->insert("temp/testblog");
	}
	
	function tearDown() {
		$this->blog->delete();
	}
    
    function testInsertDelete() {
		$e = new BlogEntry();
		$e->subject = "foo";
		$e->data = "bar baz";
		
		$currts = time();
		
		$ret = $e->insert($this->blog);
		
		$this->assertTrue($ret);
		
		$ret = $e->delete();
		
		$this->assertTrue($ret);
	}
}