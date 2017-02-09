<?php
class PathTest extends PHPUnit_Framework_TestCase {
    function testCreator() {
		$p = new Path();
		$this->assertEquals($p->path, array());
		$p = new Path('/home/foo/bar');
		$this->assertEquals($p->path, array('/home/foo/bar'));
		$p = new Path('home', 'foo', 'bar');
		$this->assertEquals($p->path , array('home', 'foo', 'bar'));
		$p = new Path('C:\test', 'foo');
		$this->assertEquals($p->path , array('C:\test', 'foo'));
	}
    
	function testIsAbsolute() {
		$p = new Path();
        $p->dirsep = Path::UNIX_SEP;
		$this->assertTrue($p->isAbsolute('/home/foo'));
		$this->assertFalse($p->isAbsolute('home/foo'));
		$p->dirsep = Path::WINDOWS_SEP;
		$this->assertTrue($p->isAbsolute('C:\test'));
	}
	
	function testGet() {
		$p = new Path('home','joe','bin');
        $p->dirsep = Path::UNIX_SEP;
		$this->assertEquals($p->getPath(), 'home/joe/bin');
		$p = new Path('/home/users','joe','bin');
        $p->dirsep = Path::UNIX_SEP;
		$this->assertEquals($p->getPath(), '/home/users/joe/bin');
		$p = new Path('C:\test\\','foo');
		$p->dirsep = Path::WINDOWS_SEP;
		$this->assertEquals($p->getPath(),'C:\test\foo');
		
		# Check dual instance/static behavior
		Path::$sep = Path::UNIX_SEP;
		$p = Path::get('home','joe','bin');
		$this->assertEquals('home/joe/bin', $p);
	}
	
	function testImplodePath() {
		$this->assertEquals(Path::implodePath('|',array('foo','|bar','|baz')), 'foo|bar|baz');
	}
	
	function testGetCanonical() {
		$p = new Path('/home/foo/../test/./file.txt');
		$this->assertEquals($p->getCanonical(),'/home/test/file.txt');
		$p = new Path('../home/foo/../test/./file.txt');
		$this->assertEquals($p->getCanonical(),'/home/test/file.txt');
		$p = new Path('C:\..\test\.\file.txt');
		$p->dirsep = '\\';
		$this->assertEquals($p->getCanonical(),'C:\test\file.txt');
	}
	
	function testAppend() {
		$p = new Path('home','joe');
		$p->append("bin");
		$this->assertEquals($p->path, array('home','joe','bin')); 
		$p->append('bar','baz');
		$this->assertEquals($p->path, array('home','joe','bin','bar','baz')); 
	}
	
	function testUrlSlashes() {
		$p = new Path('C:\Inetpub\wwwroot\foo\index.asp');
		$p->dirsep = '\\';
		$this->assertEquals($p->urlSlashes(), '/Inetpub/wwwroot/foo/index.asp');
		$this->assertEquals($p->urlSlashes('C:\Inetpub\wwwroot'), '/foo/index.asp');
        
		$p = new Path('/home/foo/bar/baz');
		$p->dirsep = '/';
		$this->assertEquals($p->urlSlashes(), '/home/foo/bar/baz');
		$this->assertEquals($p->urlSlashes('/home/foo/'), 'bar/baz');
	}
	
	function testHasPrefix() {
		$p = new Path('C:\foo\bar\baz.txt');
		$p->dirsep = "\\";
		$this->assertTrue($p->hasPrefix('C:\foo'));
		$this->assertFalse($p->hasPrefix('C:\baz'));
	}
	
	function testStripPrefix() {
		$p = new Path('C:\foo\bar\baz.txt');
		$p->dirsep = "\\";
		$this->assertEquals($p->stripPrefix('C:\foo'), '\bar\baz.txt');
		
		$p = new Path("/home/bob/test");
		$p->dirsep = "/";
		$this->assertEquals($p->stripPrefix("/home/bo"), 'b/test');
		$this->assertEquals($p->stripPrefix("/foo"), '/home/bob/test');
	}
	
	function testConvertSlashes() {
		$p = new Path('C:\foo\bar\baz.txt');
		$target = "C:/foo/bar/baz.txt";
	}
}