<?php

use Prophecy\Argument;

use LnBlog\Attachments\FileManager;

class FileManagerTest extends \PHPUnit\Framework\TestCase
{
    private $prophet;
    private $fs;

    public function testGetAll_WhenEntryContainsJpegs_ReturnsOnlyAttachedFiles() {
        $entry = $this->createBlogEntry(['test.jpg']);

        $manager = $this->createFileManager($entry->reveal());
        $files = $manager->getAll();

        $this->assertEquals(1, count($files));
        $this->assertEquals('test.jpg', $files[0]->getName());
        $this->assertEquals('./path/to/entry/test.jpg', $files[0]->getPath());
    }

    public function testGetAll_WhenBlogContainsJpegs_ReturnsOnlyAttachedFiles() {
        $blog = $this->prophet->prophesize('Blog');
        $blog->getManagedFiles()->willReturn(
            [
            'index.php',
            'pathconfig.php',
            'blogdata.ini',
            'ip_ban.txt',
            're_ban.txt',
            'plugins.xml',
            ]
        );
        $mock_blog = $blog->reveal();
        $mock_blog->home_path = './blog';
        $this->fs->scandir('./blog')->willReturn(
            [
            '.',
            '..',
            'index.php',
            'pathconfig.php',
            'blogdata.ini',
            'ip_ban.txt',
            're_ban.txt',
            'plugins.xml',
            'test.jpg',
            'cache',
            'content',
            'drafts',
            'entries',
            'feeds',
            ]
        );
        $this->fs->is_file('./blog/index.php')->willReturn(true);
        $this->fs->is_file('./blog/pathconfig.php')->willReturn(true);
        $this->fs->is_file('./blog/blogdata.ini')->willReturn(true);
        $this->fs->is_file('./blog/ip_ban.txt')->willReturn(true);
        $this->fs->is_file('./blog/re_ban.txt')->willReturn(true);
        $this->fs->is_file('./blog/plugins.xml')->willReturn(true);
        $this->fs->is_file('./blog/test.jpg')->willReturn(true);
        $this->fs->is_file('./blog/cache')->willReturn(false);
        $this->fs->is_file('./blog/content')->willReturn(false);
        $this->fs->is_file('./blog/drafts')->willReturn(false);
        $this->fs->is_file('./blog/entries')->willReturn(false);
        $this->fs->is_file('./blog/feeds')->willReturn(false);

        $manager = $this->createFileManager($mock_blog);
        $files = $manager->getAll();

        $this->assertEquals(1, count($files));
        $this->assertEquals('test.jpg', $files[0]->getName());
        $this->assertEquals('./blog/test.jpg', $files[0]->getPath());
    }

    public function testGetAll_WhenDirectoryScanFails_ThrowsRuntimeException() {
        $entry = $this->createBlogEntry();
        $this->fs->scandir('./path/to/entry/')->willReturn(false);

        $this->expectException(RuntimeException::class);

        $manager = $this->createFileManager($entry->reveal());
        $manager->getAll();
    }

    public function testRemove_WhenFileExistsForEntry_ShouldDeleteFile() {
        $entry = $this->createBlogEntry(['test.jpg']);
        $this->fs->delete('./path/to/entry/test.jpg')->willReturn(true);

        $manager = $this->createFileManager($entry->reveal());
        $manager->remove('test.jpg');

        $this->fs->delete('./path/to/entry/test.jpg')->shouldHaveBeenCalled();
    }

    public function testRemove_WhenRemoveFails_ShouldThrowRuntimeException() {
        $entry = $this->createBlogEntry(['test.jpg']);
        $this->fs->delete('./path/to/entry/test.jpg')->willReturn(false);

        $this->expectException(RuntimeException::class);

        $manager = $this->createFileManager($entry->reveal());
        $manager->remove('test.jpg');
    }

    public function testRemove_WhenFileNotInRepository_ShouldThrowFileNotFound() {
        $entry = $this->createBlogEntry();
        $this->fs->delete('./path/to/entry/test.jpg')->willReturn(false);

        $this->expectException(FileNotFound::class);

        $manager = $this->createFileManager($entry->reveal());
        $manager->remove('test.jpg');
    }

    public function testRemove_WhenFileIsOnExclusionList_ShouldThrowFileIsProtected() {
        $entry = $this->createBlogEntry();

        $this->expectException(FileIsProtected::class);

        $manager = $this->createFileManager($entry->reveal());
        $manager->remove('entry.xml');
    }

    public function testAttach_WhenFileValid_ShouldCopyFile() {
        $entry = $this->createBlogEntry();
        $this->fs->copy('/path/to/doc.pdf', './path/to/entry/')->willReturn(true);

        $manager = $this->createFileManager($entry->reveal());
        $manager->attach('/path/to/doc.pdf');

        $this->fs->copy('/path/to/doc.pdf', './path/to/entry/')->shouldHaveBeenCalled();
    }

    public function testAttach_WhenFileAlreadyExists_ShouldRecopyFile() {
        $entry = $this->createBlogEntry();
        $this->fs->copy('/path/to/doc.pdf', './path/to/entry/')->willReturn(true);

        $manager = $this->createFileManager($entry->reveal());
        $manager->attach('/path/to/doc.pdf');

        $this->fs->copy('/path/to/doc.pdf', './path/to/entry/')->shouldHaveBeenCalled();
    }

    public function testAttach_WhenNewNameProvidedForBlacklistedFile_ShouldCopyFileToNewName() {
        $entry = $this->createBlogEntry();
        $this->fs->copy('/path/to/entry.xml', './path/to/entry/example.xml')->willReturn(true);

        $manager = $this->createFileManager($entry->reveal());
        $manager->attach('/path/to/entry.xml', 'example.xml');

        $this->fs->copy('/path/to/entry.xml', './path/to/entry/example.xml')->shouldHaveBeenCalled();
    }

    public function testAttach_WhenCopyFails_ShouldThrowException() {
        $entry = $this->createBlogEntry();
        $this->fs->copy('/path/to/doc.pdf', './path/to/entry/')->willReturn(false);

        $this->expectException(RuntimeException::class);

        $manager = $this->createFileManager($entry->reveal());
        $manager->attach('/path/to/doc.pdf');
    }

    public function testAttach_WhenFileIsBlacklisted_ShouldThrowFileIsProtectedError() {
        $entry = $this->createBlogEntry();

        $this->expectException(FileIsProtected::class);

        $manager = $this->createFileManager($entry->reveal());
        $manager->attach('/path/to/entry.xml');

        $this->fs->copy(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    public function testAttach_WhenNewNameIsBlacklisted_ShouldThrowFileIsProtectedError() {
        $entry = $this->createBlogEntry();

        $this->expectException(FileIsProtected::class);

        $manager = $this->createFileManager($entry->reveal());
        $manager->attach('/path/to/test.xml', 'entry.xml');

        $this->fs->copy(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    protected function setUp(): void {
        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize('FS');
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }

    private function createFileManager($object) {
        return new FileManager($object, $this->fs->reveal());
    }

    private function createBlogEntry(array $files = []) {
        $entry = $this->prophet->prophesize('BlogEntry');
        $entry->localpath()->willReturn('./path/to/entry/');
        $entry->getManagedFiles()->willReturn(['index.php', 'entry.xml']);
        $this->setUpEntryFileListing('./path/to/entry/', $files);
        return $entry;
    }

    private function setUpEntryFileListing($path, $extra_files) {
        $files = array_merge(
            [
            '.',
            '..',
            'index.php',
            'entry.xml',
            'comments',
            ], $extra_files
        );
        $this->fs->scandir($path)->willReturn($files);
        $path = rtrim($path, '/');
        $this->fs->is_file("$path/index.php")->willReturn(true);
        $this->fs->is_file("$path/entry.xml")->willReturn(true);
        $this->fs->is_file("$path/test.jpg")->willReturn(true);
        $this->fs->is_file("$path/.")->willReturn(false);
        $this->fs->is_file("$path/..")->willReturn(false);
        $this->fs->is_file("$path/comments")->willReturn(false);
        
        foreach ($extra_files as $file) {
            $this->fs->is_file("$path/$file")->willReturn(true);
        }
    }
}
