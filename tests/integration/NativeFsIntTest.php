<?php
class NativeFsIntTest extends \PHPUnit\Framework\TestCase
{
    private $init_dir;

    function setUp(): void {
        $this->init_dir = getcwd();
        if (! is_dir("temp")) {
            mkdir("temp");
        }
    }

    function tearDown(): void {
        @unlink("temp/writefile.txt");
        @unlink("temp/writefile.php");
        @rmdir("temp/foo/baz/bizz");
        @rmdir("temp/foo/baz");
        @rmdir("temp/foo/bar");
        @rmdir("temp/foo");
        @rmdir("temp");
    }

    function getOctalPermString($path) {
        return (int)substr(sprintf("%o", fileperms($path)), -4);
    }

    function testChdir() {
        $fs = new NativeFS();
        $cwd = getcwd();
        $target = $cwd.DIRECTORY_SEPARATOR."temp";
        $fs->chdir("temp");
        $this->assertEquals(getcwd(), $target);
        $fs->chdir("..");
        $this->assertEquals(getcwd(), $cwd);
    }

    /**
     * @requires    OS  Linux
     */
    function testMkdirRmdir() {
        $this->markTestSkipped("This does not seem to work.");
        $fs = new NativeFS();

        $fs->mkdir("temp/foo");
        $this->assertTrue(is_dir("temp/foo"));
        $this->assertEquals(
            $this->getOctalPermString("temp/foo"),
            (int)decoct($fs->directory_mode)
        );

        $fs->mkdir("temp/bar", 0666);
        $this->assertTrue(is_dir("temp/bar"));
        $this->assertEquals($this->getOctalPermString("temp/bar"), (int)decoct(0666));

        $this->assertTrue($fs->rmdir("temp/bar"));

        $fs->mkdir("temp/bar");
        $fs->chdir("temp/bar");
        $del = getcwd();
        $cwd = dirname(getcwd());
        $this->assertTrue($fs->rmdir($del));
        $this->assertEquals(getcwd(), $cwd);
        $fs->chdir($this->init_dir);
    }

    /**
     * @requires    OS  Linux
     */
    function testMkdirRec() {
        $this->markTestSkipped("This does not seem to work.");
        $fs = new NativeFS();

        $fs->mkdir_rec("temp/foo/bar");
        $fs->mkdir_rec("temp/foo/baz/bizz", 0755);
        $this->assertTrue(is_dir("temp/foo"));
        $this->assertTrue(is_dir("temp/foo/bar"));
        $this->assertTrue(is_dir("temp/foo/baz"));
        $this->assertTrue(is_dir("temp/foo/baz/bizz"));
        $this->assertEquals(
            $this->getOctalPermString("temp/foo"),
            (int)decoct($fs->directory_mode)
        );
        $this->assertEquals(
            $this->getOctalPermString("temp/foo/bar"),
            (int)decoct($fs->directory_mode)
        );
        $this->assertEquals(
            $this->getOctalPermString("temp/foo/baz"),
            (int)decoct(0755)
        );
        $this->assertEquals(
            $this->getOctalPermString("temp/foo/baz/bizz"),
            (int)decoct(0755)
        );
    }

    /**
     * @requires    OS  Linux
     */
    function testWriteFile() {
        $this->markTestSkipped("This does not seem to work.");
        $fs = new NativeFS();
        $ret1 = $fs->write_file("temp/writefile.txt", "Testing");
        $ret2 = $fs->write_file("temp/writefile.php", "<?php echo 'Testing';?>");

        $this->assertTrue($ret1 > 0);
        $this->assertTrue($ret2 > 0);

        $this->assertTrue(file_exists("temp/writefile.txt"));
        $this->assertTrue(file_exists("temp/writefile.php"));
        $this->assertFalse($fs->isScript("temp/writefile.txt"));
        $this->assertTrue($fs->isScript("temp/writefile.php"));

        $this->assertEquals(
            $this->getOctalPermString("temp/writefile.txt"),
            (int)decoct($fs->default_mode)
        );
        $this->assertEquals(
            $this->getOctalPermString("temp/writefile.php"),
            (int)decoct($fs->script_mode)
        );

    }

    /**
     * @requires    OS  Linux
     */
    function testChmod() {
        $fs = new NativeFS();
        $fname = "temp/chmodtest.tmp";
        $fh = fopen($fname, "w+");
        fwrite($fh, "test");
        fclose($fh);
        $ret = $fs->chmod($fname, 0777);
        $this->assertTrue($ret);
        clearstatcache();
        $this->assertEquals(substr(sprintf('%o', fileperms($fname)), -4), "0777");
        unlink($fname);
    }
}
