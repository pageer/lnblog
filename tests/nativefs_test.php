<?php
require_once("../lib/nativefs.php");
class TestOfNativeFS extends UnitTestCase {
	function TestOfNativeFS() {
		$this->UnitTestCase("Wrapper class for built-in file writing functions");
		$this->init_dir = getcwd();
		define("FS_DEFAULT_MODE", 0666);
		define("FS_SCRIPT_MODE", 0777);
		define("FS_DIRECTORY_MODE", 0777);
	}
	
	function getOctalPermString($path) {
		return (int)substr(sprintf("%o", fileperms($path)), -4);
	}
	
	function setUp() {
		
	}
	
	function tearDown() {
		@unlink("temp/writefile.txt");
		@unlink("temp/writefile.php");
		@rmdir("temp/foo/baz/bizz");
		@rmdir("temp/foo/baz");
		@rmdir("temp/foo/bar");
		@rmdir("temp/foo");
	}
	
	function testCreator() {
		$fs = new NativeFS();
		$this->assertIdentical($fs->directory_mode, FS_DIRECTORY_MODE);
		$this->assertIdentical($fs->default_mode, FS_DEFAULT_MODE);
		$this->assertIdentical($fs->script_mode, FS_SCRIPT_MODE);
	}
	
	function testLocalpathToFSPath() {
		$fs = new NativeFS();
		$this->assertIdentical($fs->localpathToFSPath('/home/foo'), '/home/foo');
	}
	
	function testFSPathToLocalpath() {
		$fs = new NativeFS();
		$this->assertIdentical($fs->FSPathToLocalpath('/home/foo'), '/home/foo');
	}
	
	function testChdir() {
		$fs = new NativeFS();
		$cwd = getcwd();
		$target = $cwd.DIRECTORY_SEPARATOR."temp";
		$fs->chdir("temp");
		$this->assertIdentical(getcwd(), $target);
		$fs->chdir("..");
		$this->assertIdentical(getcwd(), $cwd);
	}
	
	function testMkdirRmdir() {
		$fs = new NativeFS();
		
		$fs->mkdir("temp/foo");
		$this->assertTrue(is_dir("temp/foo"));
		$this->assertIdentical($this->getOctalPermString("temp/foo"), 
		                       (int)decoct($fs->directory_mode));
		
		$fs->mkdir("temp/bar", 0666);
		$this->assertTrue(is_dir("temp/bar"));
		$this->assertIdentical($this->getOctalPermString("temp/bar"), (int)decoct(0666));
		
		$this->assertTrue($fs->rmdir("temp/bar"));
		
		$fs->mkdir("temp/bar");
		$fs->chdir("temp/bar");
		$del = getcwd();
		$cwd = dirname(getcwd());
		$this->assertTrue($fs->rmdir($del));
		$this->assertIdentical(getcwd(), $cwd);
		$fs->chdir($this->init_dir);
	}
	
	function testMkdirRec() {
		$fs = new NativeFS();
		
		$fs->mkdir_rec("temp/foo/bar");
		$fs->mkdir_rec("temp/foo/baz/bizz", 0755);
		$this->assertTrue(is_dir("temp/foo"));
		$this->assertTrue(is_dir("temp/foo/bar"));
		$this->assertTrue(is_dir("temp/foo/baz"));
		$this->assertTrue(is_dir("temp/foo/baz/bizz"));
		$this->assertIdentical($this->getOctalPermString("temp/foo"), 
		                       (int)decoct($fs->directory_mode));
		$this->assertIdentical($this->getOctalPermString("temp/foo/bar"), 
		                       (int)decoct($fs->directory_mode));
		$this->assertIdentical($this->getOctalPermString("temp/foo/baz"), 
		                       (int)decoct(0755));
		$this->assertIdentical($this->getOctalPermString("temp/foo/baz/bizz"), 
		                       (int)decoct(0755));
	}
	
	function testIsScript() {
		$fs = new NativeFS();
		$this->assertTrue($fs->isScript("temp/foo.php"));
		$this->assertFalse($fs->isScript("temp/bar.txt"));
		$this->assertFalse($fs->isScript("temp/bar"));
	}
	
	function testWriteFile() {
		$fs = new NativeFS();
		$ret1 = $fs->write_file("temp/writefile.txt", "Testing");
		$ret2 = $fs->write_file("temp/writefile.php", "<?php echo 'Testing';?>");

		$this->assertTrue($ret1 > 0);
		$this->assertTrue($ret2 > 0);
		
		$this->assertTrue(file_exists("temp/writefile.txt"));
		$this->assertTrue(file_exists("temp/writefile.php"));
		$this->assertFalse($fs->isScript("temp/writefile.txt"));
		$this->assertTrue($fs->isScript("temp/writefile.php"));
		
		$this->assertIdentical($this->getOctalPermString("temp/writefile.txt"), 
		                       (int)decoct($fs->default_mode));
		$this->assertIdentical($this->getOctalPermString("temp/writefile.php"), 
		                       (int)decoct($fs->script_mode));
		
	}
		/*
	function testRmdirRec() {
		$fs->chdir("temp/foo");
		$start = getcwd();
		$end = dirname(getcwd());
		$this->assertTrue($fs->rmdir_rec($start));
		$this->assertIdentical($end, getcwd());
		chdir("..");
	}
	*/
	
	function testChmod() {
		$fs = new NativeFS();
		$fname = "temp/chmodtest.tmp";
		$fh = fopen($fname, "w+");
		fwrite($fh,"test");
		fclose($fh);
		$ret = $fs->chmod($fname, 0777);
		$this->assertTrue($ret);
		clearstatcache();
		$this->assertEqual(substr(sprintf('%o', fileperms($fname)), -4), "0777");
		unlink($fname);
	}
}
?>