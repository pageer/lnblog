<?php
require_once("../lib/path.php");
class TestOfPath extends UnitTestCase {
	function TestOfPath() {
		$this->UnitTestCase("Path building/manipulation class");
	}
	
	function testCreator() {
		$p = new Path();
		$this->assertIdentical($p->path, array());
		$p = new Path('/home/foo/bar');
		$this->assertIdentical($p->path, array('/home/foo/bar'));
		$p = new Path('home', 'foo', 'bar');
		$this->assertIdentical($p->path , array('home', 'foo', 'bar'));
		$p->Path('fizz','buzz');
		$this->assertIdentical($p->path , array('fizz','buzz'));
		$p = new Path('C:\test', 'foo');
		$this->assertIdentical($p->path , array('C:\test', 'foo'));
	}
	
	function testIsAbsolute() {
		$p = new Path();
		$this->assertTrue($p->isAbsolute('/home/foo'));
		$this->assertFalse($p->isAbsolute('home/foo'));
		$p->sep = '\\';
		$this->assertTrue($p->isAbsolute('C:\test'));
	}
	
	function testGet() {
		$p = new Path('home','joe','bin');
		$this->assertIdentical($p->get(), 'home/joe/bin');
		$p = new Path('/home/users','joe','bin');
		$this->assertIdentical($p->get(), '/home/users/joe/bin');
		$p = new Path('C:\test\\','foo');
		$p->sep = '\\';
		$this->assertIdentical($p->get(),'C:\test\foo');
		
		# Check dual instance/static behavior
		$p = Path::get('home','joe','bin');
		$this->assertIdentical('home/joe/bin', $p);
	}
	
	function testImplodePath() {
		$this->assertIdentical(Path::implodePath('|',array('foo','|bar','|baz')), 'foo|bar|baz');
	}
	
	function testGetCanonical() {
		$p = new Path('/home/foo/../test/./file.txt');
		$this->assertIdentical($p->getCanonical(),'/home/test/file.txt');
		$p = new Path('../home/foo/../test/./file.txt');
		$this->assertIdentical($p->getCanonical(),'/home/test/file.txt');
		$p = new Path('C:\..\test\.\file.txt');
		$p->sep = '\\';
		$this->assertIdentical($p->getCanonical(),'C:\test\file.txt');
	}
	
	function testAppend() {
		$p = new Path('home','joe');
		$p->append("bin");
		$this->assertIdentical($p->path, array('home','joe','bin')); 
		$p->append('bar','baz');
		$this->assertIdentical($p->path, array('home','joe','bin','bar','baz')); 
	}
	
	function testUrlSlashes() {
		$p = new Path("C:\Inetpub\wwwroot\foo\index.asp");
		$p->sep = '\\';
		$this->assertIdentical($p->urlSlashes(), '/Inetpub/wwwroot/foo/index.asp');
		$this->assertIdentical($p->urlSlashes('C:\Inetpub\wwwroot'), '/foo/index.asp');
		
		$p->Path('/home/foo/bar/baz');
		$p->sep = '/';
		$this->assertIdentical($p->urlSlashes(), '/home/foo/bar/baz');
		$this->assertIdentical($p->urlSlashes('/home/foo/'), 'bar/baz');
	}
	
	function testHasPrefix() {
		$p = new Path("C:\foo\bar\baz.txt");
		$p->sep = "\\";
		$this->assertTrue($p->hasPrefix("C:\foo"));
		$this->assertFalse($p->hasPrefix("C:\baz"));
	}
	
	function testStripPrefix() {
		$p = new Path("C:\foo\bar\baz.txt");
		$p->sep = "\\";
		$this->assertIdentical($p->stripPrefix("C:\foo"), '\bar\baz.txt');
		
		$p->Path("/home/bob/test");
		$p->sep = "/";
		$this->assertIdentical($p->stripPrefix("/home/bo"), 'b/test');
		$this->assertIdentical($p->stripPrefix("/foo"), '/home/bob/test');
	}
	
	function testConvertSlashes() {
		$p = new Path("C:\foo\bar\baz.txt");
		$target = "C:/foo/bar/baz.txt";
	}
	
	function testAppendSlash() {
		$p = new Path("/home/foo");
		$this->assertEqual("/home/foo/", Path::appendSlash("/home/foo"));
		$this->assertEqual("/home/foo/", Path::appendSlash("/home/foo/"));
	}
}
?>