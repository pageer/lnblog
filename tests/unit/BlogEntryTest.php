<?php
class BlogEntryTest extends PHPUnit\Framework\TestCase {
    function testCalcPrettyPermalink() {
		$e = new BlogEntry();
		
		$e->subject = "My test entry";
		$this->assertEquals("My_test_entry.php", $e->calcPrettyPermalink());
		
		$e->subject = "A test of .... my entry";
		$this->assertEquals("A_test_of_my_entry.php", $e->calcPrettyPermalink());
		
		$e->subject = "A test of \"my\" entry";
		$this->assertEquals("A_test_of__my__entry.php", $e->calcPrettyPermalink());
	}
}
