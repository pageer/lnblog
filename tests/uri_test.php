<?php
require_once("../lib/uri.php");
class TestOfURI extends UnitTestCase {
	function TestOfNativeFS() {
	
	}
	
	function setUp(){
	
	}
	
	function tearDown() {
	
	}
	
	function initUri() {
		$u = new URI();
		$u->host = "example.com";
		$u->port = 80;
		$u->path = "/foo/bar.php";
		$u->query_string = array("fizz"=>1, "buzz"=>"bob");
		$u->fragment = "something";
		return $u;
	}
	
	function testConstructor() {
		$u = new URI();
		$this->assertEqual($_SERVER['SERVER_NAME'], $u->host);
	}
	
	function testGetQueryString() {
		$u = $this->initUri();
		$this->assertEqual("?fizz=1&buzz=bob", $u->getQueryString());
		$this->assertEqual("?fizz=1|buzz=bob", $u->getQueryString('|'));
		$u->query_string['a'] = 'b';
		$this->assertEqual("?fizz=1&buzz=bob&a=b", $u->getQueryString());
	}
	
	function testGetFragment() {
		$u = $this->initUri();
		$this->assertEqual("#something", $u->getFragment());
	}

	function testGetPort() {
		$u = $this->initUri();
		$u->port = 80;
		$this->assertEqual("", $u->getPort());
		$u->port = 443;
		$this->assertEqual(":443", $u->getPort());
	}
	
	function testGet() {
		$u = $this->initUri();
		$this->assertEqual("http://example.com/foo/bar.php?fizz=1&buzz=bob#something", $u->get());
		$u->query_string = array();
		$u->port = 443;
		$this->assertEqual("http://example.com:443/foo/bar.php#something", $u->get());
	}
	
	function testGetPrintable() {
		$u = $this->initUri();
		$this->assertEqual("http://example.com/foo/bar.php?fizz=1&amp;buzz=bob#something",
		                   $u->getPrintable());
	}
	
	function testPathAppend() {
		$u = $this->initUri();
		$old_path = $u->path;
		$u->appendPath("baz");
		$this->assertEqual($u->path, $old_path."/baz");
		$u->path = "/foo/";
		$u->appendPath("/baz");
		$this->assertEqual($u->path, "/foo/baz");
	}
	
	function testClearQueryString() {
		$u = $this->initUri();
		$u->clearQueryString();
		$this->assertIdentical($u->query_string, array());
	}
	
	function testAddQuery() {
		$u = $this->initUri();
		$u->addQuery("me", "you");
		$this->assertIdentical($u->query_string, array("fizz"=>1, "buzz"=>"bob", "me"=>"you"));
	}
}
?>