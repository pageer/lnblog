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
