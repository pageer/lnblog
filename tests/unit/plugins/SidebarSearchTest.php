<?php

namespace LnBlog\Tests\Plugins;

use BasePages;
use Blog;
use BlogEntry;
use Generator;
use LnBlog\Tests\LnBlogBaseTestCase;
use Page;
use Prophecy\Argument;
use SidebarSearch;

require_once __DIR__ . "/../../../plugins/sidebar_search.php";

class SidebarSearchTest extends LnBlogBaseTestCase
{
    /**
     * @dataProvider getSearchTypes
     */
    public function testSidebarPanel_SearchType_InOutput(string $searchType, string $expectedUrl): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog->getUrl()->willReturn('https://foo.com/');

        $plugin = new SidebarSearch($blog->reveal());
        $plugin->caption = 'SearchPanel';
        $plugin->label = 'SearchLabel';
        $plugin->search_provider = $searchType; 

        ob_start();
        $plugin->sidebar_panel();
        $output = ob_get_clean();

        $this->assertStringContainsString('SearchPanel', $output);
        $this->assertStringContainsString('SearchLabel', $output);
        $this->assertStringContainsString($expectedUrl, $output);
    }

    public function getSearchTypes(): Generator {
        yield 'Native search' => ['native', '?plugin=sidebar_search'];
        yield 'Google search' => ['google', 'https://www.google.com/search'];
        yield 'Bing search' => ['bing', 'https://www.bing.com/search'];
    }

    public function testSidebarPanel_LabelInBox_OutputsPlaceholder(): void {
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->isBlog()->willReturn(true);
        $blog->getUrl()->willReturn('https://foo.com/');

        $plugin = new SidebarSearch($blog->reveal());
        $plugin->label = 'SearchLabel';
        $plugin->label_in_box = true;

        ob_start();
        $plugin->sidebar_panel();
        $output = ob_get_clean();

        $this->assertStringContainsString('placeholder="SearchLabel"', $output);
    }

    public function testOutputPage_NoResults_ShowsMessage(): void {
        $page = $this->getMockPage();
        $web_page = $this->prophet->prophesize(BasePages::class);
        $web_page->getPage()->willReturn($page);

        $entry1 = $this->prophet->prophesize(BlogEntry::class);
        $entry1->permalink()->willReturn('https://foo.com/link1');
        $entry1->data = 'This is a thing';
        $entry1->subject = 'Blah, blah';

        $blog = $this->prophet->prophesize(Blog::class);
        $blog->name = 'test';
        $blog->getEntries(-1)->willReturn([$entry1->reveal()]);
        $_GET['sb_search_terms'] = 'test';

        $plugin = new SidebarSearch($blog->reveal());
        $plugin->outputPage($web_page->reveal());

        $this->assertStringContainsString('No search results found', $page->body);
        $this->assertStringNotContainsString('https://foo.com/link1', $page->body);
    }

    public function testOutputPage_NoEntries_ShowsMessage(): void {
        $page = $this->getMockPage();
        $web_page = $this->prophet->prophesize(BasePages::class);
        $web_page->getPage()->willReturn($page);

        $blog = $this->prophet->prophesize(Blog::class);
        $blog->name = 'test';
        $blog->getEntries(-1)->willReturn([]);
        $_GET['sb_search_terms'] = 'test';

        $plugin = new SidebarSearch($blog->reveal());
        $plugin->outputPage($web_page->reveal());

        $this->assertStringContainsString('No search results found', $page->body);
    }

    public function testOutputPage_NoSearchTerms_ShowsMessage(): void {
        $page = $this->getMockPage();
        $web_page = $this->prophet->prophesize(BasePages::class);
        $web_page->getPage()->willReturn($page);
        $blog = $this->prophet->prophesize(Blog::class);
        $blog->name = 'test';
        $_GET['sb_search_terms'] = '';

        $plugin = new SidebarSearch($blog->reveal());
        $plugin->outputPage($web_page->reveal());

        $this->assertStringContainsString('No search terms', $page->body);
    }

    public function testOutputPage_Results_ShowsResultTitles(): void {
        $page = $this->getMockPage();
        $web_page = $this->prophet->prophesize(BasePages::class);
        $web_page->getPage()->willReturn($page);

        $entry1 = $this->prophet->prophesize(BlogEntry::class);
        $entry1->permalink()->willReturn('https://foo.com/link1');
        $entry1->data = 'This is a test';
        $entry1->subject = 'Blah, blah';
        $entry2 = $this->prophet->prophesize(BlogEntry::class);
        $entry2->permalink()->willReturn('https://foo.com/link2');
        $entry2->data = 'This is a thing';
        $entry2->subject = 'Test';

        $blog = $this->prophet->prophesize(Blog::class);
        $blog->name = 'test';
        $blog->getEntries(-1)->willReturn([
            $entry1->reveal(),
            $entry2->reveal(),
        ]);

        $_GET['sb_search_terms'] = 'test';

        $plugin = new SidebarSearch($blog->reveal());
        $plugin->outputPage($web_page->reveal());

        $expected_values = [
            'Search results for "test"',
            'Test',
            'Blah, blah',
            'https://foo.com/link1',
            'https://foo.com/link2',
        ];
        foreach ($expected_values as $value) {
            $this->assertStringContainsString($value, $page->body);
        }
    }

    public function testOutputPage_HtmlInSearchTerm_SanitizedInOutput(): void {
        $page = $this->getMockPage();
        $web_page = $this->prophet->prophesize(BasePages::class);
        $web_page->getPage()->willReturn($page);

        $entry1 = $this->prophet->prophesize(BlogEntry::class);
        $entry1->permalink()->willReturn('https://foo.com/link1');
        $entry1->data = 'This is a test';
        $entry1->subject = 'Blah, blah';
        $entry2 = $this->prophet->prophesize(BlogEntry::class);
        $entry2->permalink()->willReturn('https://foo.com/link2');
        $entry2->data = 'This is a test thing';
        $entry2->subject = 'Test';

        $blog = $this->prophet->prophesize(Blog::class);
        $blog->name = 'test';
        $blog->getEntries(-1)->willReturn([
            $entry1->reveal(),
            $entry2->reveal(),
        ]);

        $_GET['sb_search_terms'] = '<b>test</b>';

        $plugin = new SidebarSearch($blog->reveal());
        $plugin->outputPage($web_page->reveal());

        $this->assertStringNotContainsString('<b>', $page->body);
    }

    private function getMockPage(): Page {
        return new class () extends Page {
            public $body;
            public function display($page_body, $blog = false) {
                $this->body = $page_body;
            }
        };
    }
}
