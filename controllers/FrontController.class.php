<?php
class FrontController {
    
    protected $uri = '';
    
    protected $routes = array(
        '|^admin(?:/(.*))?$|'  => 'AdminController',
        '|^manage(?:/(.*))?|'  => 'ManageController',
        '|^entries(?:/(.*))?|' => 'ViewController',
    );
    
    public function __construct($uri) {
        $this->uri = trim($uri, '/');
    }
    
    public function route() {
        $controller = null;
        $data = null;
        foreach ($this->routes as $route => $controller_class) {
            if (preg_match($route, $this->uri, $matches)) {
                $controller = $controller_class;
                if (isset($matches[1])) {
                    $data = array_slice($matches, 1);
                }
            }
        }
        if ($controller) {
            $ctl = new $controller($this->url);
            $result = $ctl->handle_request();
        } else {
            $result = Response::not_found();
            $result->render_response();
        }
    }
}