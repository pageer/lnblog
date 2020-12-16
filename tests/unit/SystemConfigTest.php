<?php

use Prophecy\Argument;

class SystemConfigTest extends \PHPUnit\Framework\TestCase
{
    private $prophet;
    private $fs;
    private $globals;

    public function testGetBlogs_ReturnsRegistry() {
        $urlpath = new UrlPath('place/foobar/', 'https://foobar.example.com/');
        $config = $this->createSystemConfig();
        $config->registerBlog('foobar', $urlpath);
        $config->registerBlog('bazzbuzz', new UrlPath('', ''));
        $config->unregisterBlog('bazzbuzz');

        $blogs = $config->blogRegistry();

        $this->assertEquals(['foobar' => $urlpath], $blogs);
    }

    public function testInstallRootUserData_ReturnSetValue() {
        $installRoot = new UrlPath('whatever', 'https://example.com/lnblog/');
        $userData = new UrlPath('whatever/userdata/', 'https://example.com/userdata/');
        $config = $this->createSystemConfig();
        $config->installRoot(new UrlPath('whatever', 'https://example.com/lnblog/'));
        $config->userData($userData);

        $this->assertEquals($installRoot, $config->installRoot());
        $this->assertEquals($userData, $config->userData());
    }

    public function testWriteConfig_WithRegistry_WritesFile() {
        $expected_output  = "<?php\n";
        $expected_output .= "return array (\n";
        $expected_output .= "  'INSTALL_ROOT' => \n";
        $expected_output .= "  UrlPath::__set_state(array(\n";
        $expected_output .= "     'path' => 'whatever',\n";
        $expected_output .= "     'url' => 'https://example.com/lnblog/',\n";
        $expected_output .= "  )),\n";
        $expected_output .= "  'USER_DATA' => \n";
        $expected_output .= "  UrlPath::__set_state(array(\n";
        $expected_output .= "     'path' => 'whatever/userdata/',\n";
        $expected_output .= "     'url' => 'https://example.com/userdata/',\n";
        $expected_output .= "  )),\n";
        $expected_output .= "  'BLOGS' => \n";
        $expected_output .= "  array (\n";
        $expected_output .= "    'foobar' => \n";
        $expected_output .= "    UrlPath::__set_state(array(\n";
        $expected_output .= "       'path' => 'place/foobar/',\n";
        $expected_output .= "       'url' => 'https://foobar.example.com/',\n";
        $expected_output .= "    )),\n";
        $expected_output .= "  ),\n";
        $expected_output .= ");\n";
        $this->fs->write_file(Argument::containingString('/pathconfig.php'), $expected_output)->shouldBeCalled()->willReturn(123);

        $config = $this->createSystemConfig();
        $config->installRoot(new UrlPath('whatever', 'https://example.com/lnblog/'));
        $config->userData(new UrlPath('whatever/userdata/', 'https://example.com/userdata/'));
        $config->registerBlog('foobar', new UrlPath('place/foobar/', 'https://foobar.example.com/'));
        $config->writeConfig();
    }
    public function testWriteConfig_WriteFails_ThrowsFileWriteFailedException() {
        $this->fs->write_file(Argument::containingString('/pathconfig.php'), Argument::any())->willReturn(false);

        $this->expectException(FileWriteFailed::class);

        $config = $this->createSystemConfig();
        $config->installRoot(new UrlPath('whatever', 'https://example.com/lnblog/'));
        $config->userData(new UrlPath('whatever/userdata/', 'https://example.com/userdata/'));
        $config->registerBlog('foobar', new UrlPath('place/foobar/', 'https://foobar.example.com/'));
        $config->writeConfig();
    }

    public function testConfigExists_WithConfig_ReturnsTrue() {
        $this->fs->file_exists(Argument::containingString('/pathconfig.php'))->willReturn(true);

        $config = $this->createSystemConfig();
        $this->assertTrue($config->configExists());
    }

    public function testConfigExists_WithoutConfig_ReturnsFalse() {
        $this->fs->file_exists(Argument::containingString('/pathconfig.php'))->willReturn(false);

        $config = $this->createSystemConfig();
        $this->assertFalse($config->configExists());
    }

    protected function setUp(): void {
        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();
        $this->globals = $this->prophet->prophesize(GlobalFunctions::class);
        $this->fs = $this->prophet->prophesize(FS::class);
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }

    private function createSystemConfig() {
        return new SystemConfig($this->fs->reveal(), $this->globals->reveal());
    }
}
