<?php
require_once("../lib/blogentry.php");

class TestOfBlogEntry extends UnitTestCase {

	function TestOfBlogEntry() {
		$this->UnitTestCase("BlogEntry class");
	}
	
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
	
	function testCalcPrettyPermalink() {
		$e = new BlogEntry();
		
		$e->subject = "My test entry";
		$this->assertIdentical("My_test_entry.php", $e->calcPrettyPermalink());
		
		$e->subject = "A test of .... my entry";
		$this->assertIdentical("A_test_of_my_entry.php", $e->calcPrettyPermalink());
		
		$e->subject = "A test of \"my\" entry";
		$this->assertIdentical("A_test_of_my_entry.php", $e->calcPrettyPermalink());
	}
	
}
?>