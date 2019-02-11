<?php
class UriTest extends PHPUnit\Framework\TestCase {
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
        $_SERVER['SERVER_NAME'] = 'foobar.com';
		$u = new URI();
		$this->assertEquals('foobar.com', $u->host);
	}
	
	function testGetQueryString() {
		$u = $this->initUri();
		$this->assertEquals("?fizz=1&buzz=bob", $u->getQueryString());
		$this->assertEquals("?fizz=1|buzz=bob", $u->getQueryString('|'));
		$u->query_string['a'] = 'b';
		$this->assertEquals("?fizz=1&buzz=bob&a=b", $u->getQueryString());
	}
	
	function testGetFragment() {
		$u = $this->initUri();
		$this->assertEquals("#something", $u->getFragment());
	}

	function testGetPort() {
		$u = $this->initUri();
		$u->port = 80;
		$this->assertEquals("", $u->getPort());
		$u->port = 443;
		$this->assertEquals(":443", $u->getPort());
	}
	
	function testGet() {
		$u = $this->initUri();
		$this->assertEquals("http://example.com/foo/bar.php?fizz=1&buzz=bob#something", $u->get());
		$u->query_string = array();
		$u->port = 443;
		$this->assertEquals("http://example.com:443/foo/bar.php#something", $u->get());
	}
	
	function testGetPrintable() {
		$u = $this->initUri();
		$this->assertEquals("http://example.com/foo/bar.php?fizz=1&amp;buzz=bob#something",
		                   $u->getPrintable());
	}
	
	function testPathAppend() {
		$u = $this->initUri();
		$old_path = $u->path;
		$u->appendPath("baz");
		$this->assertEquals($u->path, $old_path."/baz");
		$u->path = "/foo/";
		$u->appendPath("/baz");
		$this->assertEquals($u->path, "/foo/baz");
	}
	
	function testClearQueryString() {
		$u = $this->initUri();
		$u->clearQueryString();
		$this->assertEquals($u->query_string, array());
	}
	
	function testAddQuery() {
		$u = $this->initUri();
		$u->addQuery("me", "you");
		$this->assertEquals($u->query_string, array("fizz"=>1, "buzz"=>"bob", "me"=>"you"));
	}
}
