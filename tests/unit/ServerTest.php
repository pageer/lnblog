<?php
class ServerTest extends PHPUnit_Framework_TestCase {
    function testGetServerSubdomainURI() {
		$s = new Server();
		$_SERVER['SERVER_NAME'] = 'myhost';
		$this->assertEquals($_SERVER['SERVER_NAME'], 'myhost');
		$this->assertEquals($s->getURI(), 'http://'.$_SERVER['SERVER_NAME']);
		$this->assertEquals($s->getURI(true), 'https://'.$_SERVER['SERVER_NAME']);
		$_SERVER['HTTPS'] = "on";
		$this->assertEquals($s->getURI(), 'https://'.$_SERVER['SERVER_NAME']);
		$_SERVER['HTTPS'] = "oN";
		$this->assertEquals($_SERVER['HTTPS'], 'oN');
		$this->assertEquals($s->getURI(), 'https://'.$_SERVER['SERVER_NAME']);
		
		
		$s->domain = 'domainfoo.test';
		unset($_SERVER['HTTPS']);
		$this->assertEquals($s->getSubdomainURI(), 'http://'.$_SERVER['SERVER_NAME']);
		$this->assertEquals($s->getSubdomainURI('bar'), 'http://bar.domainfoo.test');
		$this->assertEquals($s->getSubdomainURI('bar', true), 'https://bar.domainfoo.test');
		$_SERVER['HTTPS'] = "oN";
		$this->assertEquals($s->getSubdomainURI('bar'), 'https://bar.domainfoo.test');
	}
}