<?php

class TestPages extends BasePages {
    public $action_map = array();
    public $method_called = '';
    public $whitelist = [];
    public $page_mock;
    
    public function __call($name, $args) {
        $this->method_called = $name;
    }
    
    protected function getActionMap() {
        return $this->action_map;
    }
    
    protected function defaultAction() {
        $this->method_called = 'defaultAction';
    }
    
    protected function getPage() {
        return $this->page_mock;
    }

    public function getCsrfWhitelist() {
        return $this->whitelist;
    }
}

class OtherTestPages extends TestPages {
    public static $last_action;
    
    public function __call($name, $args) {
        self::$last_action = $name;
    }
}

class BasePagesTest extends PHPUnit\Framework\TestCase {
    
    private $page;
    private $page_mock;
    private $fs;
    private $globals;
    private $prophet;
    
    public function testRouteRequest_WhenNoActionAvailable_RunsDefaultAction() {
        $_GET = array();
        
        $this->page->routeRequest();
        
        $this->assertEquals('defaultAction', $this->page->method_called);
    }
    
    public function testRouteRequest_WhenActionSpecifiedAndInMap_RunsActionMethod() {
        $this->page->action_map = array('something' => 'someaction');
        
        $this->page->routeRequest('something');
        
        $this->assertEquals('someaction', $this->page->method_called);
    }
    
    public function testRouteRequest_WhenActionInQueryStringAndInMap_RunsActionMethod() {
        $_GET = array('action' => 'something');
        $this->page->action_map = array('something' => 'someaction');
        
        $this->page->routeRequest();
        
        $this->assertEquals('someaction', $this->page->method_called);
    }
    
    public function testRouteRequest_WhenActionIsClassAndMethod_InstantiatesClassAndCallsMethod() {
        $this->page->action_map = array('something' => [OtherTestPages::class, 'someaction']);
        
        $this->page->routeRequest('something');
        
        $this->assertEquals('someaction', OtherTestPages::$last_action);
    }
    
    public function testRouteRequest_WhenActionIsNotInMap_CallsDefaultAction() {
        $this->page->action_map = array('thing1' => 'thing2');
        
        $this->page->routeRequest('foobar');
        
        $this->assertEquals('defaultAction', $this->page->method_called);
    }
    
    public function testRouteRequest_WhenActionIsScript_ReadsFileToOutput() {
        
    }

    public function testRouteRequest_WhenPostAndNoToken_ReturnsBadRequest() {
        $_POST = ['foo' => 'bar'];
        $_SERVER['HTTP_HOST'] = 'somedomain.com';
        $_SERVER['HTTP_ORIGIN'] = 'http://somedomain.com/test';
        $this->page->action_map = array('thing1' => 'thing2');

        $this->page_mock->error(400)->shouldBeCalled();
        
        $this->page->routeRequest();
    }

    public function testRouteRequest_WhenPostHasFilesButNoData_ReturnsBadRequest() {
        $_POST = [];
        $_FILES = ['somefile'];
        $_SERVER['HTTP_HOST'] = 'somedomain.com';
        $_SERVER['HTTP_ORIGIN'] = 'http://somedomain.com/test';
        $this->page->action_map = array('thing1' => 'thing2');

        $this->page_mock->error(400)->shouldBeCalled();
        
        $this->page->routeRequest();
    }

    public function testRouteRequest_WhenPostAndTokenInvalid_ReturnsBadRequest() {
        $token = $this->page->getCsrfToken();
        $_POST = ['foo' => 'bar', BasePages::TOKEN_POST_FIELD => 'asdf1234'];
        $_SERVER['HTTP_HOST'] = 'somedomain.com';
        $_SERVER['HTTP_ORIGIN'] = 'http://somedomain.com/test';
        $this->page->action_map = array('thing1' => 'thing2');

        $this->page_mock->error(400)->shouldBeCalled();
        
        $this->page->routeRequest();
    }
    
    public function testRouteRequest_WhenPostAndTokenValid_ReturnsSuccess() {
        $token = $this->page->getCsrfToken();
        $_POST = ['foo' => 'bar', BasePages::TOKEN_POST_FIELD => $token];
        $_SERVER['HTTP_HOST'] = 'somedomain.com';
        $_SERVER['HTTP_ORIGIN'] = 'http://somedomain.com/test';
        $this->page->action_map = array('thing1' => 'thing2');

        $this->page_mock->error(400)->shouldNotBeCalled();
        
        $this->page->routeRequest();
    }
    
    public function testRouteRequest_WhenPostNoTokenRouteWhitelisted_ReturnsSuccess() {
        $token = $this->page->getCsrfToken();
        $_POST = ['foo' => 'bar'];
        $_SERVER['HTTP_HOST'] = 'somedomain.com';
        $_SERVER['HTTP_ORIGIN'] = 'http://somedomain.com/test';
        $this->page->action_map = array('thing1' => 'thing2');
        $this->page->whitelist = ['thing1'];

        $this->page_mock->error(400)->shouldNotBeCalled();
        
        $this->page->routeRequest('thing1');
    }
    
    public function testRouteRequest_WhenPostAndCannotDetermineTargetOrigin_ReturnsBadRequest() {
        $token = $this->page->getCsrfToken();
        $_POST = ['foo' => 'bar', BasePages::TOKEN_POST_FIELD => $token];
        $_SERVER['HTTP_ORIGIN'] = 'http://somedomain.com/asdf';
        $this->page->action_map = array('thing1' => 'thing2');

        $this->page_mock->error(400)->shouldBeCalled();
        
        $this->page->routeRequest('thing1');
    }
    
    public function testRouteRequest_WhenPostAndCannotDetermineSourceOrigin_ReturnsBadRequest() {
        $token = $this->page->getCsrfToken();
        $_POST = ['foo' => 'bar', BasePages::TOKEN_POST_FIELD => $token];
        $_SERVER['HTTP_HOST'] = 'somedomain.com';
        $this->page->action_map = array('thing1' => 'thing2');

        $this->page_mock->error(400)->shouldBeCalled();
        
        $this->page->routeRequest('thing1');
    }
    
    public function testRouteRequest_WhenPostAndSourceDoesNotMatchTargetOrigin_ReturnsBadRequest() {
        $token = $this->page->getCsrfToken();
        $_POST = ['foo' => 'bar', BasePages::TOKEN_POST_FIELD => $token];
        $_SERVER['HTTP_HOST'] = 'somedomain.com';
        $_SERVER['HTTP_ORIGIN'] = 'http://otherdomain.com/thing';
        $this->page->action_map = array('thing1' => 'thing2');

        $this->page_mock->error(400)->shouldBeCalled();
        
        $this->page->routeRequest('thing1');
    }

    public function testRouteRequest_WhenPostAndMissingSourceOriginAndNotBlocking_ReturnsSuccess() {
        $token = $this->page->getCsrfToken();
        $this->globals->defined("BLOCK_ON_MISSING_ORIGIN")->willReturn(true);
        $this->globals->constant("BLOCK_ON_MISSING_ORIGIN")->willReturn(false);
        $_POST = ['foo' => 'bar', BasePages::TOKEN_POST_FIELD => $token];
        $_SERVER['HTTP_HOST'] = 'somedomain.com';
        $this->page->action_map = array('thing1' => 'thing2');

        $this->page_mock->error(400)->shouldNotBeCalled();
        
        $this->page->routeRequest('thing1');
    }
    
    public function testRouteRequest_WhenPostAndAlternateSourceAndTargetOriginsDoNotMatch_ReturnsBadRequest() {
        $token = $this->page->getCsrfToken();
        $_POST = ['foo' => 'bar', BasePages::TOKEN_POST_FIELD => $token];
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'somedomain.com';
        $_SERVER['HTTP_REFERER'] = 'http://otherdomain.com/thing';
        $this->page->action_map = array('thing1' => 'thing2');

        $this->page_mock->error(400)->shouldBeCalled();
        
        $this->page->routeRequest('thing1');
    }
    
    protected function setUp(): void {
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize(FS::class);
        $this->globals = $this->prophet->prophesize(GlobalFunctions::class);
        $this->page_mock = $this->prophet->prophesize(Page::class);
        $this->page = new TestPages($this->fs->reveal(), $this->globals->reveal());
        $this->page_mock = $this->prophet->prophesize(Page::class);
        $this->page->page_mock = $this->page_mock->reveal();
        OtherTestPages::$last_action = '';
    }
    
    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }
}
