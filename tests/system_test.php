<?php
#require_once("../lib/creators.php");
require_once("../lib/system.php");
require_once("../lib/iniparser.php");

Mock::generate("INIParser");

class TestOfSystem extends UnitTestCase {
	function TestOfSystem() {
		$this->UnitTestCase("Class for system-wide settings and security");
	}
	
	/*
	function testLocalpathToURI() {
		$s = new System();
		$server = $_SERVER['SERVER_NAME'];
		
		$s->docroot = "/home/pageer/public_html/";
		$path = '/home/pageer/public_html/myblog/feeds/news.xml';
		$target = "http://$server/~pageer/myblog/feeds/news.xml";
		$this->assertIdentical($s->pathToURI($path), $target);
		
		$s->docroot = "/var/www/";
		
		$path = '/var/www/mysite/';
		$target = "http://$server/mysite/";
		$this->assertIdentical($s->pathToURI($path), $target);
		
		$path = '/var/www/mysite/foobar.png';
		$target = "http://$server/mysite/foobar.png";
		$this->assertIdentical($s->pathToURI($path), $target);
		
		$path = '/home/bob/www/index.html';
		$this->assertFalse($s->pathToURI($path));
		
		$s->docroot = 'C:\Inetpub\wwwroot\\';
		$path = 'C:\Inetpub\wwwroot\foo\index.asp';
		$target = "http://$server/foo/index.asp";
		$this->assertIdentical($s->pathToURI($path), $target);
	}
	*/
}
?>