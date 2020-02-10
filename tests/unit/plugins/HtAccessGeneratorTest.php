<?php

require_once __DIR__ . "/../../../plugins/htaccess_generator.php";

class HtAccessGeneratorTest extends \PHPUnit\Framework\TestCase {

    public function testCreateFile_WhenNoBlogHtaccess_CreatesNewFile() {
        $expected_content = implode("\n", $this->getLnBlogSection());
        $fs = $this->prophet->prophesize(FS::class);
        $fs->file_exists('./.htaccess')->willReturn(false);
        $blog_instance = $this->createBlogInstance();

        $fs->write_file('./.htaccess', $expected_content)->shouldBeCalled();

        $generator = new HtAccessGenerator($fs->reveal());
        $generator->create_file($blog_instance);
    }

    public function testCreateFile_WhenBlogHtaccessExistsBuNotOurs_AddsLnBlogBlock() {
        $existing_file = $this->getExistingFileSection();
        $all_lines = array_merge($existing_file, $this->getLnBlogSection());
        $expected_content = implode("\n", $all_lines);
        $fs = $this->prophet->prophesize(FS::class);
        $fs->file_exists('./.htaccess')->willReturn(true);
        $fs->file('./.htaccess')->willReturn($existing_file);
        $blog_instance = $this->createBlogInstance();

        $fs->write_file('./.htaccess', $expected_content)->shouldBeCalled();

        $generator = new HtAccessGenerator($fs->reveal());
        $generator->create_file($blog_instance);
    }

    public function testCreateFile_WhenBlogHtaccessExistsWithLnBlogBlock_UpdateBlock() {
        $existing_file = array_merge(
            $this->getExistingFileSection(),
            [
                '# START LnBlog section - 0.2.2',
                'Options +FollowSymlinks',
                'RewriteEngine on',
                'RewriteRule ^(.+/comments/).+\..txt$ $1 [nc]',
                'RewriteRule ^(.+/trackback/).+\..txt$ $1 [nc]',
                'RewriteRule ^(.+/)current.htm$ $1 [nc]',
                'RewriteRule ^(.+/)[0-9_]+.htm$ $1 [nc]',
                'RewriteRule ^(.+/)blogdata.txt$ $1 [nc]',
                'RewriteRule ^(.+/)deleted.*$ $1 [nc]',
                '# END LnBlog section',
            ]
        );
        $expected_content = implode("\n", array_merge(
            $this->getExistingFileSection(),
            $this->getLnBlogSection()
        ));
        $fs = $this->prophet->prophesize(FS::class);
        $fs->file_exists('./.htaccess')->willReturn(true);
        $fs->file('./.htaccess')->willReturn($existing_file);
        $blog_instance = $this->createBlogInstance();

        $fs->write_file('./.htaccess', $expected_content)->shouldBeCalled();

        $generator = new HtAccessGenerator($fs->reveal());
        $generator->create_file($blog_instance);
    }

    public function testCreateFile_WhenBlogHtaccessExistsWithLnBlogBlockInMiddle_UpdateBlock() {
        $existing_file = [
            '# This is a .htaccess file',
            '# START LnBlog section - 0.2.2',
            '# END LnBlog section',
            '# The end',
        ];
        $expected_content = implode("\n", array_merge(
            ['# This is a .htaccess file',],
            $this->getLnBlogSection(),
            ['# The end']
        ));
        $fs = $this->prophet->prophesize(FS::class);
        $fs->file_exists('./.htaccess')->willReturn(true);
        $fs->file('./.htaccess')->willReturn($existing_file);
        $blog_instance = $this->createBlogInstance();

        $fs->write_file('./.htaccess', $expected_content)->shouldBeCalled();

        $generator = new HtAccessGenerator($fs->reveal());
        $generator->create_file($blog_instance);
    }

    public function testCreateFile_WhenCopyParentEnabledAndNoCurrentHtaccess_UsesParent() {
        $parent_file = [
            '# This is a .htaccess file',
        ];
        $expected_content = implode("\n", array_merge(
            ['# This is a .htaccess file',],
            $this->getLnBlogSection()
        ));
        $fs = $this->prophet->prophesize(FS::class);
        $fs->file_exists('./foo/.htaccess')->willReturn(false);
        $fs->file_exists('./.htaccess')->willReturn(true);
        $fs->file('./.htaccess')->willReturn($parent_file);
        $blog_instance = $this->createBlogInstance();
        $blog_instance->home_path = './foo/';

        $fs->write_file('./foo/.htaccess', $expected_content)->shouldBeCalled();

        $generator = new HtAccessGenerator($fs->reveal());
        $generator->copy_parent = true;
        $generator->create_file($blog_instance);
    }

    protected function setUp(): void {
        Path::$sep = '/';
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize('FS');
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }

    private function getExistingFileSection() {
        return [
            'AddHandler application/x-httpd-php7 .php',
            '',
            '# This file has been edited by SureSupport.com automatic tools on Fri Nov  2 04:03:54 2012, to make it',
            '# compatible with Apache 2.4. The original file has been saved as .htaccess.apache1',
            '',
            '# [omeiW7Xu] mod_deflate enabled by SureSupport.com (Apache 2.4 Upgrade)',
            '# Fri Nov  2 04:03:54 2012 - Please do not include anything between these comment lines!',
            '<IfModule deflate_module>',
            '  AddOutputFilterByType DEFLATE text/css text/csv text/html text/plain text/rich              text text/sgml text/tab-separated-values application/javascript application/x-ja              vascript httpd/unix-directory',
            '  AddOutputFilter DEFLATE html htm shtml php php4 pl rb py cgi css js txt',
            '  BrowserMatch ^Mozilla/4 gzip-only-text/html',
            '  BrowserMatch ^Mozilla/4\.0[678] no-gzip',
            '  BrowserMatch \bMSIE !no-gzip !gzip-only-text/html',
            '</IfModule>',
            '# [omeiW7Xu]',
        ];
    }

    private function getLnBlogSection() {
        return [
            '# START LnBlog section - 1.0.0',
            '# This section managed by LnBlog HtAccessGenerator',
            'RewriteEngine On',
            'Options +FollowSymlinks',
            '',
            '<FilesMatch "^\.htaccess|blogdata\.ini|ip_ban\.txt|re_ban\.txt|plugins\.xml$">',
            '    Order Allow,Deny',
            '    Deny from all',
            '</FilesMatch>',
            '',
            'RewriteRule ^(entries/\d{4}/\d{2}/\d{2}_\d{4,6}/comments/).+\.xml$ $1 [nc]',
            'RewriteRule ^(entries/\d{4}/\d{2}/\d{2}_\d{4,6}/pingback/).+\.xml$ $1 [nc]',
            'RewriteRule ^(entries/\d{4}/\d{2}/\d{2}_\d{4,6}/trackback/).+\.xml$ $1 [nc]',
            'RewriteRule ^(entries/\d{4}/\d{2}/\d{2}_\d{4,6}/)entry.xml$ $1 [nc]',
            'RewriteRule ^(entries/\d{4}/\d{2}/\d{2}_\d{4,6}/comments/)deleted.*$ $1 [nc]',
            'RewriteRule ^(content/.+/comments/).+\.xml$ $1 [nc]',
            'RewriteRule ^(content/.+/pingback/).+\.xml$ $1 [nc]',
            'RewriteRule ^(content/.+/trackback/).+\.xml$ $1 [nc]',
            'RewriteRule ^(content/.+/)entry.xml$ $1 [nc]',
            'RewriteRule ^(content/.+/comments/)deleted.*$ $1 [nc]',
            '# END LnBlog section',
        ];
    }

    private function createBlogInstance() {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->getManagedFiles()->willReturn([
            'index.php',
            'pathconfig.php',
            'blogdata.ini',
            'ip_ban.txt',
            're_ban.txt',
            'plugins.xml',
        ]);
        $blog_instance = $blog->reveal();
        $blog_instance->home_path = '.';
        return $blog_instance;
    }
}
