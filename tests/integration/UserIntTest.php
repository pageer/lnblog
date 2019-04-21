<?php

class UserIntTest extends \PHPUnit\Framework\TestCase {
    private $created_users = [];

    public function test_When_Reading_Old_Password_Login_Succeeds() {
        $username = "testuser" . rand(100, 1000);
        $this->created_users[] = $username;
        $password = "password";
        $salt = "QWERTASDFG";
        $hash = md5($password . $salt);
        $this->createOldUserDirectory($username, $hash, $salt);

        $user = new User($username);
        $user->enableCookies(false);
        $result = $user->login($password);

        $this->assertTrue($result);
    }

    public function test_When_Reading_New_Password_Login_Succeeds() {
        $username = "testuser" . rand(100, 1000);
        $this->created_users[] = $username;
        $password = "password";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->createNewUserDirectory($username, $hash);
       
        $user = new User($username);
        $user->enableCookies(false);
        $result = $user->login($password);

        $this->assertTrue($result);
    }

    public function test_When_Old_Login_Succeeds_Writes_New_Password_File() {
        $username = "testuser" . rand(100, 1000);
        $this->created_users[] = $username;
        $password = "password";
        $salt = "QWERTASDFG";
        $hash = md5($password . $salt);
        $this->createOldUserDirectory($username, $hash, $salt);

        $user = new User($username);
        $user->enableCookies(false);
        $result = $user->login($password);

        $this->assertTrue($result);
        $password_file = implode(DIRECTORY_SEPARATOR, [USER_DATA_PATH, $username, "passwd.php"]);
        $file_content = file_get_contents($password_file);
        $this->assertCount(2, explode("\n", trim($file_content)));
        $this->assertFalse(strpos($file_content, $hash));
        $this->assertRegexp("/return '.*';/", $file_content);
    }

    protected function setUp() {
        $this->created_users = [];
    }

    protected function tearDown() {
        foreach ($this->created_users as $username) {
            $this->removeUserDirectory($username);
        }
    }

    private function createOldUserDirectory($username, $hash, $salt) {
        $directory = USER_DATA_PATH . DIRECTORY_SEPARATOR . $username;
        $passwd_file = $directory . DIRECTORY_SEPARATOR . "passwd.php";
        mkdir($directory);
        $content = "<?php\n\$pwd = \"$hash\";\n\$salt = \"$salt\";\n?>";
        file_put_contents($passwd_file, $content);
    }

    private function createNewUserDirectory($username, $hash) {
        $directory = USER_DATA_PATH . DIRECTORY_SEPARATOR . $username;
        $passwd_file = $directory . DIRECTORY_SEPARATOR . "passwd.php";
        mkdir($directory);
        $content = "<?php\nreturn '$hash';\n";
        file_put_contents($passwd_file, $content);
    }

    private function removeUserDirectory(string $username) {
        $fs = new NativeFS();
        $fs->rmdir_rec(USER_DATA_PATH . DIRECTORY_SEPARATOR . $username);
    }
}
