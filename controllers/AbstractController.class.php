<?php
// NOTE: Just extend Page for now to make porting easier.
abstract class AbstractController extends Page {
	
    const PERM_LOGGED_IN = 'loggedIn';
    const PERM_IS_ADMIN = 'isAdmin';
    const PERM_CAN_ADDI= 'canAdd';
    const PERM_CAN_EDIT = 'canEdit';
    const PERM_CAN_DELETE = 'canDelete';
    const PERM_CAN_VIEW = 'canView';
    
    protected $referece_object = null;
    protected $current_user = null;
    protected $route = '';
    
    // Entry format:
    // 'route_regex_or_func => array(
    //     'action' => 'function_name',
    //     'perms' => array(self::PERM_LOGGED_IN),
    // )
	protected $route_stack = array();
	
    public $data = array();
    
    protected abstract function getReferenceObject();
    
	public function __construct($route) {
        $this->route = $route;
	}
    
    public function route() {
        $ret = $this->findRoute();
        if (is_string($ret) && file_exists($this->get_template_path($ret))) {
            $tpl = new Template($this->get_template_path($ret));
            foreach ($this->data as $key => $val) {
                $tpl->set($key, $val);
            }
            $this->display($tpl->process());
        } elseif (is_string($ret)) {
            echo $ret;
        } elseif (is_array($ret) || is_object($ret)) {
            echo json_encode($ret);
        }
    }
    
    protected function get_template_path($tpl) {
        $theme = defined('THEME_NAME') ? THEME_NAME : 'default';
        return mkpath(INSTALL_ROOT, 'themes', $theme, 'templates', "{$tpl}_tpl.php"); 
    }
    
    protected function findRoute() {
        foreach ($this->route_stack as $rt => $data) {
            if ( (method_exists($this, $rt) && $this->$rt())
                 || preg_match($rt, $this->route)) {
                return $this->runAction($data);
            }
        }
        return $this->routeNotFound();
    }
    
    protected function runAction($action_data) {
        $obj = $this->getReferenceObject();
        if (isset($action_data['perms'])) {
            $this->current_user = User::get();
            $perms = is_array($action_data['perms']) ? $action_data['perms'] : array($action_data['perms']);
            $result = false;
            foreach ($perms as $perm) {
                $result &= $this->permCheck($perm);
            }
            if (! $result) {
                return $this->routeNotAllowed();
            }
        }
    }
    
    protected function routeNotFound() {
        Page::instance()->error(404);
    }
    
    protected function routeNotAllowed() {
        Page::instance()->error(403);
    }
    
    protected function permCheck($perm) {
        switch ($perm) {
            case self::PERM_LOGGED_IN:
                return $this->current_user->checkLogin();
            case self::PERM_IS_ADMIN:
                return $this->current_user->isAdministrator();
            case self::PERM_CAN_ADD:
                return System::instance()->canAddTo($this->referece_object, $this->current_user);
            case self::PERM_CAN_EDIT:
                return System::instance()->canModify($this->referece_object, $this->current_user);
            case self::PERM_CAN_DELETE:
                return System::instance()->canDelete($this->referece_object, $this->current_user);
            case self::PERM_CAN_VIEW:
                return true;
            default:
                return false;
        }
    }
    
    public function get($var) {
        return isset($_GET[$var]) ? $_GET[$var] : null;
    }
    
    public function post($var) {
        return isset($_POST[$var]) ? $_POST[$var] : null;
    }
	
	public function isPost() {
		return !empty($_POST);
	}
	
	public function hasPost($fields) {
		if (! is_array($fields)) {
			$fields = func_get_args();
		}
		return $this->hasFields($fields, $_POST);
	}
	
	public function hasFields($fields, $array) {
		foreach ($fields as $f) {
			if (! isset($array[$f]) || $array[$f] === '') {
				return false;
			}
		}
		return true;
	}
}