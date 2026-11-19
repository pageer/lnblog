<?php

namespace LnBlog\Tests\Storage;

use PHPUnit\Framework\Attributes\DataProvider;
use Generator;
use LnBlog\Storage\IdResolver;
use LnBlog\Tests\LnBlogBaseTestCase;
use SystemConfig;
use UrlPath;

class IdResolverTest extends LnBlogBaseTestCase
{
    private array $blogList;

    public static function baseTestProvider(): Generator {
        yield 'blog' => [
            'testblog',
            '/path/to/testblog'
        ];
        yield 'entry' => [
            'testblog/entries/2024/01/02_1234',
            '/path/to/testblog/entries/2024/01/02_1234/entry.xml',
        ];
        yield 'pingback' => [
            'testblog/entries/2020/12/10_1949/pingback/#pingback1641077732',
            '/path/to/testblog/entries/2020/12/10_1949/pingback/1641077732.xml',
        ];
        yield 'trackback' => [
            'testblog/entries/2020/12/10_1949/pingback/#pingback1641077732',
            '/path/to/testblog/entries/2020/12/10_1949/pingback/1641077732.xml',
        ];
        yield 'comment' => [
            'testblog/entries/2022/07/10_1612/comments/#comment2022-09-05_213808',
            '/path/to/testblog/entries/2022/07/10_1612/comments/2022-09-05_213808.xml',
        ];
    }

    #[DataProvider('baseTestProvider')]
    public function testSimpleItemResolution($globalId, $expectedPath): void {
        $resolver = $this->createResolver();
        $result = $resolver->idToPath($globalId);

        $this->assertEquals($expectedPath, $result->itemPath);
        $this->assertEquals('/path/to/testblog', $result->blogPath);
    }

    #[DataProvider('baseTestProvider')]
    public function testPathToIdRoundTrip($globalId, $path): void {
        $resolver = $this->createResolver();
        $result = $resolver->pathToId($path);

        $this->assertEquals($globalId, $result);
    }

    protected function setUp(): void {
        parent::setUp();

        $this->blogList = [
            'testblog' => new UrlPath('/path/to/testblog', 'http://testblog.com/'),
        ];
    }

    private function createResolver(): IdResolver {
        $sysConfig = $this->prophet->prophesize(SystemConfig::class);
        $sysConfig->blogRegistry()->willReturn($this->blogList);
        return new IdResolver($sysConfig->reveal());
    }
}
