<?php

class TestPages extends BasePages {
    public $action_map = array();
    public $method_called = '';
    
    public function __call($name, $args) {
        $this->method_called = $name;
    }
    
    protected function getActionMap() {
        return $this->action_map;
    }
    
    protected function defaultAction() {
        $this->method_called = 'defaultAction';
    }
}

class OtherTestPages extends TestPages {
    public static $last_action;
    
    public function __call($name, $args) {
        self::$last_action = $name;
    }
}

class BasePagesTest extends PHPUnit_Framework_TestCase {
    
    private $page;
    private $fs;
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
        $this->page->action_map = array('something' => 'OtherTestPages::someaction');
        
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
    
    protected function setUp() {
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize('FS');
        $this->page = new TestPages($this->fs->reveal());
        OtherTestPages::$last_action = '';
    }
    
    protected function tearDown() {
        $this->prophet->checkPredictions();
    }
}