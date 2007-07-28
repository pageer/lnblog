<?php
require_once("../lib/utils.php");
class TestOfUtils extends UnitTestCase {
	function TestOfUtils() {
		$this->UnitTestCase("Utility function library");
	}
	
	function setUp() {
		mkdir("temp/testing");
		mkdir("temp/testing/foo");
		mkdir("temp/testing/bar");
		$fh = fopen("temp/testing/test.txt","w+");
		fwrite($fh,"Testing");
		fclose($fh);
		$fh = fopen("temp/testing/baz.txt","w+");
		fwrite($fh,"Testing");
		fclose($fh);
	}
	
	function tearDown() {
		unlink("temp/testing/baz.txt");
		unlink("temp/testing/test.txt");
		rmdir("temp/testing/bar");
		rmdir("temp/testing/foo");
		rmdir("temp/testing");
	}
	
	function test_scan_directory() {
		
	}
	
}
?>