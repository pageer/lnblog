<?php
class BlogEntryTest extends PHPUnit\Framework\TestCase {
    function testCalcPrettyPermalink() {
		$e = new BlogEntry();
		
		$e->subject = "My test entry";
		$this->assertEquals("my-test-entry.php", $e->calcPrettyPermalink());
		
		$e->subject = "A test of .... my entry";
		$this->assertEquals("a-test-of-my-entry.php", $e->calcPrettyPermalink());
		
		$e->subject = "A test of \"my\" entry";
		$this->assertEquals("a-test-of-my-entry.php", $e->calcPrettyPermalink());
	}
}
