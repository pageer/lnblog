<?php
require_once("../lib/server.php");
class TestOfServer extends UnitTestCase {

	function TestOfServer() {
		$this->UnitTestCase("Server class");
	}
	function testGetServerSubdomainURI() {
		$s = new Server();
		$_SERVER['SERVER_NAME'] = 'myhost';
		$this->assertIdentical($_SERVER['SERVER_NAME'], 'myhost');
		$this->assertIdentical($s->getURI(), 'http://'.$_SERVER['SERVER_NAME']);
		$this->assertIdentical($s->getURI(true), 'https://'.$_SERVER['SERVER_NAME']);
		$_SERVER['HTTPS'] = "on";
		$this->assertIdentical($s->getURI(), 'https://'.$_SERVER['SERVER_NAME']);
		$_SERVER['HTTPS'] = "oN";
		$this->assertIdentical($_SERVER['HTTPS'], 'oN');
		$this->assertIdentical($s->getURI(), 'https://'.$_SERVER['SERVER_NAME']);
		
		
		$s->domain = 'domainfoo.test';
		unset($_SERVER['HTTPS']);
		$this->assertIdentical($s->getSubdomainURI(), 'http://'.$_SERVER['SERVER_NAME']);
		$this->assertIdentical($s->getSubdomainURI('bar'), 'http://bar.domainfoo.test');
		$this->assertIdentical($s->getSubdomainURI('bar', true), 'https://bar.domainfoo.test');
		$_SERVER['HTTPS'] = "oN";
		$this->assertIdentical($s->getSubdomainURI('bar'), 'https://bar.domainfoo.test');
	}
}
?>