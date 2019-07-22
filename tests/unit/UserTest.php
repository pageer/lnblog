<?php

use Prophecy\Argument;

class UserTest extends \PHPUnit\Framework\TestCase {

    public function testLogin_WhenSessionAuthAndIpLock_SetsLoginCookies() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => true]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');

        $this->assertTrue($logged_in);
        $this->assertContains('May 23 1970 21:21:18', $_COOKIE[LAST_LOGIN_TIME]);
        $this->assertContains('bob', $_COOKIE[CURRENT_USER]);
        $this->assertContains(md5('127.0.0.1May 23 1970 21:21:18'), $_COOKIE[LOGIN_TOKEN]);
    }

    public function testLogin_WhenSessionAuthAndIpLock_SetsSessionData() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => true]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');

        $this->assertTrue($logged_in);
        $this->assertEquals('bob', $_SESSION[CURRENT_USER]);
        $this->assertEquals(md5('127.0.0.1May 23 1970 21:21:18'), $_SESSION[LOGIN_TOKEN]);
        $this->assertEquals('May 23 1970 21:21:18', $_SESSION[LAST_LOGIN_TIME]);
    }

    public function testLogin_WhenCookieAuthAndIpLock_SetsLoginCookies() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => false, 'LOGIN_IP_LOCK' => true]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');

        $this->assertTrue($logged_in);
        $this->assertContains('bob', $_COOKIE[CURRENT_USER]);
        $this->assertContains(md5($user->passwd . '127.0.0.1'), $_COOKIE[PW_HASH]);
    }

    public function testLogin_WhenPasswordDoesNotMatch_ReturnsFalse() {
        $this->setConstantReturns([]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('abcde');

        $this->assertFalse($logged_in);
    }

    public function testLogin_WhenPasswordIsWhitespace_ReturnsFalse() {
        $this->setConstantReturns([]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login(" \t \n ");

        $this->assertFalse($logged_in);
    }

    public function testLogin_WhenUsernameIsWhitespace_ReturnsFalse() {
        $this->setConstantReturns([]);

        $user = $this->createUser(" \n ", '12345', $new_format = true);
        $logged_in = $user->login('12345');

        $this->assertFalse($logged_in);
    }

    public function testCheckLogin_WhenSessionAuthAndIpLockEnabled_VerifiesLoginToken() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => true]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');
        $check_login = $user->checkLogin();

        $this->assertTrue($check_login);
    }

    public function testCheckLogin_WhenCookieAuthAndIpLockEnabled_VerifiesLoginToken() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => false, 'LOGIN_IP_LOCK' => true]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');
        $check_login = $user->checkLogin();

        $this->assertTrue($check_login);
    }

    public function testCheckLogin_WhenUsernameIsEmpty_ReturnsFalse() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => true]);

        $user = $this->createUser('', '12345', $new_format = true);
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    public function testCheckLogin_WhenSessionAuthAndCookieNotPresent_ReturnsFalse() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => true]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');
        unset($_COOKIE[LAST_LOGIN_TIME]);
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    public function testCheckLogin_WhenSessionAuthWithIpLockEnabledAndIpAddressChanges_ReturnsFalse() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => true]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $logged_in = $user->login('12345');
        $_SERVER['REMOTE_ADDR'] = '5.6.7.8';
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    public function testCheckLogin_WhenSessionAuthWithIpLockDisabledAndIpAddressChanges_ReturnsTrue() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => false]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $logged_in = $user->login('12345');
        $_SERVER['REMOTE_ADDR'] = '5.6.7.8';
        $check_login = $user->checkLogin();

        $this->assertTrue($check_login);
    }

    public function testCheckLogin_WhenSessionAuthWithIpLockDisabledAndUserAgentChanges_ReturnsFalse() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => false]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['HTTP_USER_AGENT'] = 'FooBrowser/1.0';
        $logged_in = $user->login('12345');
        $_SERVER['HTTP_USER_AGENT'] = 'Buzzifier/2.1';
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    public function testCheckLogin_WhenCookieAuthWithIpLockEnabledAndIpAddressChanges_ReturnsFalse() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => false, 'LOGIN_IP_LOCK' => true]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $logged_in = $user->login('12345');
        $_SERVER['REMOTE_ADDR'] = '5.6.7.8';
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    public function testCheckLogin_WhenCookieAuthWithIpLockDisabledAndIpAddressChanges_ReturnsTrue() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => false, 'LOGIN_IP_LOCK' => false]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $logged_in = $user->login('12345');
        $_SERVER['REMOTE_ADDR'] = '5.6.7.8';
        $check_login = $user->checkLogin();

        $this->assertTrue($check_login);
    }

    public function testCheckLogin_WhenCookieAuthWithIpLockDisabledAndUserAgentChanges_ReturnsFalse() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => false, 'LOGIN_IP_LOCK' => false]);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['HTTP_USER_AGENT'] = 'FooBrowser/1.0';
        $logged_in = $user->login('12345');
        $_SERVER['HTTP_USER_AGENT'] = 'Buzzifier/2.1';
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    protected function setUp() {
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize('NativeFS');
        $this->globals = $this->prophet->prophesize(GlobalFunctions::class);

        $this->globals->setcookie(Argument::cetera())->will(function ($args) {
            $_COOKIE[$args[0]] = $args[1];
        });
        $this->globals->time()->willReturn(12345678);
    }

    protected function tearDown() {
        $this->prophet->checkPredictions();
    }

    private function createUser($username, $password, $new_format) {
        $user =  new User($username, '', $this->fs->reveal(), $this->globals->reveal());
        $user->passwd = password_hash('12345', PASSWORD_DEFAULT);
        $user->salt = $new_format ? false : '';
        return $user;
    }

    private function setConstantReturns($configs = []) {
        $defaults = [
            'AUTH_USE_SESSION' => true,
            'LOGIN_IP_LOCK' => true,
            'FORCE_HTTPS_LOGIN' => false,
        ];
        $configs = array_merge($defaults, $configs);
        $this->globals->defined(Argument::any())->will(function ($args) use ($configs) {
            return isset($configs[$args[0]]);
        });
        $this->globals->constant(Argument::any())->will(function ($args) use ($configs) {
            if (isset($configs[$args[0]])) {
                return $configs[$args[0]];
            }
            return constant($args[0]);
        });
    }
}
