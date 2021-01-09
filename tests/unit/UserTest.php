<?php

use Prophecy\Argument;
use LnBlog\User\AuthLog;
use LnBlog\User\LoginLimiter;

class UserTest extends \PHPUnit\Framework\TestCase
{
    private $prophet;
    private $fs;
    private $globals;
    private $loginLimiter;

    public function testAuthenticateCredentials_WhenSuccess_ReturnsTrueAndLogs() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $_SERVER['HTTP_USER_AGENT'] = 'Chrome';
        $this->setConstantReturns([]);
        $this->globals->time()->willReturn(12345);
        $user = $this->createUser('bob', 'password', $new_format = true);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);

        $time = new DateTime("@12345");
        $log = AuthLog::success($time, '1.2.3.4', 'Chrome');
        $this->loginLimiter->logAttempt($log)->shouldBeCalled();

        $result = $user->authenticateCredentials('password');

        $this->assertTrue($result);
    }

    public function testAuthenticateUser_WhenFailure_ReturnsFalseAndLogs() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $_SERVER['HTTP_USER_AGENT'] = 'Chrome';
        $this->setConstantReturns([]);
        $this->globals->time()->willReturn(12345);
        $user = $this->createUser('bob', 'asdf1234', true);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);

        $time = new DateTime("@12345");
        $log = AuthLog::failure($time, '1.2.3.4', 'Chrome');
        $this->loginLimiter->logAttempt($log)->shouldBeCalled();

        $result = $user->authenticateCredentials('password');

        $this->assertFalse($result);
    }

    public function testAuthenticateUser_WhenFailureIsLast_LogsAndThrows() {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $_SERVER['HTTP_USER_AGENT'] = 'Chrome';
        $this->setConstantReturns([]);
        $this->globals->time()->willReturn(12345);
        $user = $this->createUser('bob', 'asdf1234', true);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true, false);

        $time = new DateTime("@12345");
        $log = AuthLog::failure($time, '1.2.3.4', 'Chrome');
        $this->loginLimiter->logAttempt($log)->shouldBeCalled();

        $this->expectExceptionObject(new UserAccountLocked());

        $user->authenticateCredentials('password');
    }

    public function testAuthenticateUser_WhenLockedOut_ThrowsWithLoginNotCalled() {
        $this->setConstantReturns([]);
        $user = $this->createUser('bob', 'password', true);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(false);

        $this->expectExceptionObject(new UserLockedOut());

        $user->authenticateCredentials('password');
    }

    public function testLogin_WhenSessionAuthAndIpLock_SetsLoginCookies() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => true]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');

        $this->assertTrue($logged_in);
        $this->assertStringContainsString('May 23 1970 21:21:18', $_COOKIE[LAST_LOGIN_TIME]);
        $this->assertStringContainsString('bob', $_COOKIE[CURRENT_USER]);
        $this->assertStringContainsString(md5('127.0.0.1May 23 1970 21:21:18'), $_COOKIE[LOGIN_TOKEN]);
    }

    public function testLogin_WhenSessionAuthAndIpLock_SetsSessionData() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => true]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');

        $this->assertTrue($logged_in);
        $this->assertEquals('bob', $_SESSION[CURRENT_USER]);
        $this->assertEquals(md5('127.0.0.1May 23 1970 21:21:18'), $_SESSION[LOGIN_TOKEN]);
        $this->assertEquals('May 23 1970 21:21:18', $_SESSION[LAST_LOGIN_TIME]);
    }

    public function testLogin_WhenCookieAuthAndIpLock_SetsLoginCookies() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => false, 'LOGIN_IP_LOCK' => true]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');

        $this->assertTrue($logged_in);
        $this->assertStringContainsString('bob', $_COOKIE[CURRENT_USER]);
        $this->assertStringContainsString(md5($user->passwd . '127.0.0.1'), $_COOKIE[PW_HASH]);
    }

    public function testLogin_WhenPasswordDoesNotMatch_ReturnsFalse() {
        $this->setConstantReturns([]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

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
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');
        $check_login = $user->checkLogin();

        $this->assertTrue($check_login);
    }

    public function testCheckLogin_WhenCookieAuthAndIpLockEnabled_VerifiesLoginToken() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => false, 'LOGIN_IP_LOCK' => true]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

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
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $logged_in = $user->login('12345');
        unset($_COOKIE[LAST_LOGIN_TIME]);
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    public function testCheckLogin_WhenSessionAuthWithIpLockEnabledAndIpAddressChanges_ReturnsFalse() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => true]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $logged_in = $user->login('12345');
        $_SERVER['REMOTE_ADDR'] = '5.6.7.8';
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    public function testCheckLogin_WhenSessionAuthWithIpLockDisabledAndIpAddressChanges_ReturnsTrue() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => false]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $logged_in = $user->login('12345');
        $_SERVER['REMOTE_ADDR'] = '5.6.7.8';
        $check_login = $user->checkLogin();

        $this->assertTrue($check_login);
    }

    public function testCheckLogin_WhenSessionAuthWithIpLockDisabledAndUserAgentChanges_ReturnsFalse() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => false]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['HTTP_USER_AGENT'] = 'FooBrowser/1.0';
        $logged_in = $user->login('12345');
        $_SERVER['HTTP_USER_AGENT'] = 'Buzzifier/2.1';
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    public function testCheckLogin_WhenCookieAuthWithIpLockEnabledAndIpAddressChanges_ReturnsFalse() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => false, 'LOGIN_IP_LOCK' => true]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $logged_in = $user->login('12345');
        $_SERVER['REMOTE_ADDR'] = '5.6.7.8';
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    public function testCheckLogin_WhenCookieAuthWithIpLockDisabledAndIpAddressChanges_ReturnsTrue() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => false, 'LOGIN_IP_LOCK' => false]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $logged_in = $user->login('12345');
        $_SERVER['REMOTE_ADDR'] = '5.6.7.8';
        $check_login = $user->checkLogin();

        $this->assertTrue($check_login);
    }

    public function testCheckLogin_WhenCookieAuthWithIpLockDisabledAndUserAgentChanges_ReturnsFalse() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => false, 'LOGIN_IP_LOCK' => false]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);
        $this->loginLimiter->logAttempt(Argument::any())->shouldBeCalled();

        $user = $this->createUser('bob', '12345', $new_format = true);
        $_SERVER['HTTP_USER_AGENT'] = 'FooBrowser/1.0';
        $logged_in = $user->login('12345');
        $_SERVER['HTTP_USER_AGENT'] = 'Buzzifier/2.1';
        $check_login = $user->checkLogin();

        $this->assertFalse($check_login);
    }

    public function testCheckLogin_WhenLoginStatusForced_ReturnsTrue() {
        $this->setConstantReturns(['AUTH_USE_SESSION' => true, 'LOGIN_IP_LOCK' => true]);
        $this->loginLimiter->canLogIn(Argument::any())->willReturn(true);

        $user = $this->createUser('bob', '12345', $new_format = true);
        $forced_login_undefined = $user->checkLogin();
        $user->forceLoggedIn(true);
        $forced_login_on = $user->checkLogin();
        $user->forceLoggedIn(false);
        $forced_login_off = $user->checkLogin();

        $this->assertFalse($forced_login_undefined);
        $this->assertTrue($forced_login_on);
        $this->assertFalse($forced_login_off);
    }

    public function testCreateAndVerifyPasswordReset_WhenTokenCreated_ShouldVerify() {
        $this->setConstantReturns();
        $this->globals->time()->willReturn(12345, 12346);
        $this->configureMocksToReadAndWritePwreset('bob');

        $user = $this->createUserWithoutDataRead('bob', '12345');
        $token = $user->createPasswordReset();
        $result = $user->verifyPasswordReset($token);

        $this->assertTrue($result);
    }

    public function testCreateAndVerifyPasswordReset_WhenTokenHasExpired_ShouldNotVerify() {
        $this->setConstantReturns();
        // Second time, for verify, is past expiration.
        $this->globals->time()->willReturn(12345, 112346);
        $this->configureMocksToReadAndWritePwreset('bob');

        $user = $this->createUserWithoutDataRead('bob', '12345');
        $token = $user->createPasswordReset();
        $result = $user->verifyPasswordReset($token);

        $this->assertFalse($result);
    }

    public function testCreateAndVerifyPasswordReset_WhenVerifyingBadToken_ShouldNotVerify() {
        $this->setConstantReturns();
        $this->globals->time()->willReturn(12345, 12346);
        $this->configureMocksToReadAndWritePwreset('bob');

        $user = $this->createUserWithoutDataRead('bob', '12345');
        $token = $user->createPasswordReset();
        $result = $user->verifyPasswordReset('thisisajunktoken');

        $this->assertFalse($result);
    }

    public function testCreatePasswordReset_WhenMultipleAttemptsWithinSeconds_ThrowsRateLimitError() {
        $this->setConstantReturns();
        $this->globals->time()->willReturn(12345, 12346);

        $this->configureMocksToReadAndWritePwreset('bob');

        $this->expectException(RateLimitExceeded::class);

        $user = $this->createUserWithoutDataRead('bob', '12345');
        $token1 = $user->createPasswordReset();
        $token2 = $user->createPasswordReset();
    }

    public function testInvalidatePasswordReset_WhenTokenExists_TokenNoLongerValidates() {
        $this->setConstantReturns();
        $this->globals->time()->willReturn(12345, 12446, 12447);
        $this->configureMocksToReadAndWritePwreset('bob');

        $user = $this->createUserWithoutDataRead('bob', '12345');
        $token1 = $user->createPasswordReset();
        $token2 = $user->createPasswordReset();
        $user->invalidatePasswordReset($token1);
        $result = $user->verifyPasswordReset($token1);

        $this->assertFalse($result);
    }

    public function testInvalidatePasswordReset_WhenNoTokenAreLeft_DeletesPwresetFile() {
        $this->setConstantReturns();
        $this->globals->time()->willReturn(12345, 12446, 12447);
        $this->configureMocksToReadAndWritePwreset('bob');

        $this->fs->delete(Path::mk('userdata', 'bob', 'pwreset.php'))->shouldBeCalled();

        $user = $this->createUserWithoutDataRead('bob', '12345');
        $token1 = $user->createPasswordReset();
        $user->invalidatePasswordReset($token1);
    }

    protected function setUp(): void {
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize('NativeFS');
        $this->globals = $this->prophet->prophesize(GlobalFunctions::class);
        $this->loginLimiter = $this->prophet->prophesize(LoginLimiter::class);

        /** @phpstan-ignore-next-line */
        $this->globals->setcookie(Argument::cetera())->will(
            function ($args) {
                $_COOKIE[$args[0]] = $args[1];
            }
        );
        /** @phpstan-ignore-next-line */
        $this->globals->time()->willReturn(12345678);
    }

    protected function tearDown(): void {
        $this->prophet->checkPredictions();
    }

    private function createUser($username, $password, $new_format) {
        $user = new User(
            $username,
            $this->fs->reveal(),
            $this->globals->reveal(),
            $this->loginLimiter->reveal()
        );
        $user->passwd = password_hash($password, PASSWORD_DEFAULT);
        $user->salt = $new_format ? false : '';
        $this->setUpUserExists($username);
        return $user;
    }

    # Create a user without having to mock out the reading of metadata files.
    private function createUserWithoutDataRead($username, $password) {
        $user = $this->createUser('', $password, $new_format = true);
        $user->username = $username;
        $this->setUpUserExists($username);
        return $user;
    }

    private function configureMocksToReadAndWritePwreset($username) {
        $content = null;
        $file_path = Path::mk('userdata', $username, 'pwreset.php');
        $this->fs->write_file($file_path, Argument::any())->will(
            function($args) use (&$content) {
                // The pwreset.php is a PHP file, so we can get the contents
                // by stripping the php header and eval'ing it.
                $string_data = trim(str_replace('<?php', '', $args[1]));
                $content = eval($string_data);
                return true;
            }
        );
        $this->fs->file_exists($file_path)->willReturn(
            function ($args) use (&$content) {
                return $content !== null;
            }
        );
        $this->globals->include($file_path)->will(
            function ($args) use (&$content) {
                return $content;
            }
        );
    }

    private function setUpUserExists($username) {
        $this->fs->realpath("userdata/$username/passwd.php")->willReturn(true);
        $this->fs->realpath("userdata/$username/user.xml")->willReturn(true);
        $this->fs->realpath("userdata/$username/user.ini")->willReturn(false);
    }

    private function setConstantReturns($configs = []) {
        $defaults = [
            'AUTH_USE_SESSION' => true,
            'LOGIN_IP_LOCK' => true,
            'FORCE_HTTPS_LOGIN' => false,
            'USER_DATA_PATH' => 'userdata',
        ];
        SystemConfig::instance()->userData(new UrlPath('userdata', ''));
        $configs = array_merge($defaults, $configs);
        $this->globals->defined(Argument::any())->will(
            function ($args) use ($configs) {
                return isset($configs[$args[0]]);
            }
        );
        $this->globals->constant(Argument::any())->will(
            function ($args) use ($configs) {
                if (isset($configs[$args[0]])) {
                    return $configs[$args[0]];
                }
                return constant($args[0]);
            }
        );
    }
}
