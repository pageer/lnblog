<?php

use LnBlog\User\AuthLog;
use LnBlog\User\LoginLimiter;
use Prophecy\Argument;

class UserLimitTest extends PHPUnit\Framework\TestCase
{
    private $prophet;
    private $fs;
    private $globals;

    public function testGetLoginAttempts_WhenFileHasLogs_ReturnObjectArray() {
        $this->setUpFileToReturn(
            'bob', [
            [
                'status' => 'success',
                'time' => '2020-01-02T12:13:14-05:00',
                'ip' => '1.2.3.4',
                'user_agent' => 'Chrome'
            ], [
                'status' => 'failure',
                'time' => '2020-01-03T13:14:15-05:00',
                'ip' => '2.3.4.5',
                'user_agent' => 'Firefox'
            ]
            ]
        );

        $limiter = $this->createLoginLimiter(new User('bob'));
        $logins = $limiter->getLoginAttempts();

        $expected_logs = [
            AuthLog::success(new DateTime('2020-01-02T12:13:14-05:00'), '1.2.3.4', 'Chrome'),
            AuthLog::failure(new DateTime('2020-01-03T13:14:15-05:00'), '2.3.4.5', 'Firefox'),
        ];
        $this->assertEquals($expected_logs, $logins);
    }

    public function testGetLoginAttempts_WhenFileDoesNotExist_ReturnsEmpty() {
        $this->globals->constant('USER_DATA_PATH')->willReturn('userdata');
        $this->fs->file_exists('userdata/bob/logins.json')->willReturn(false);

        $limiter = $this->createLoginLimiter(new User('bob'));
        $logins = $limiter->getLoginAttempts();

        $this->assertEmpty($logins);
    }

    public function testGetLoginAttempts_WhenFileIsEmpty_ReturnsEmpty() {
        $this->globals->constant('USER_DATA_PATH')->willReturn('userdata');
        $this->fs->file_exists('userdata/bob/logins.json')->willReturn(true);
        $this->fs->read_file('userdata/bob/logins.json')->willReturn('');

        $limiter = $this->createLoginLimiter(new User('bob'));
        $logins = $limiter->getLoginAttempts();

        $this->assertEmpty($logins);
    }

    public function testLogAttempt_WhenLogEmpty_WritesLog() {
        $this->globals->constant('USER_DATA_PATH')->willReturn('userdata');
        $this->fs->file_exists('userdata/bob/logins.json')->willReturn(true);
        $this->fs->read_file('userdata/bob/logins.json')->willReturn('');

        $expected_data = json_encode(
            [
            [
                'status' => 'success',
                'time' => '2020-01-02T12:13:14-05:00',
                'ip' => '1.2.3.4',
                'user_agent' => 'Chrome'
            ]
            ]
        );
        $this->fs->write_file('userdata/bob/logins.json', $expected_data)
                 ->willReturn(100)
                 ->shouldBeCalled();

        $log = AuthLog::success(new DateTime('2020-01-02T12:13:14'), '1.2.3.4', 'Chrome');
        $limiter = $this->createLoginLimiter(new User('bob'));
        $limiter->logAttempt($log);
    }

    public function testLogAttempt_WhenLogOverflows_Truncate() {
        $test_row = [
            'status' => 'success',
            'time' => '2020-01-02T12:13:14-05:00',
            'ip' => '1.2.3.4',
            'user_agent' => 'Chrome'
        ];
        $file_content = json_encode(array_fill(0, 150, $test_row));
        $this->globals->constant('USER_DATA_PATH')->willReturn('userdata');
        $this->fs->file_exists('userdata/bob/logins.json')->willReturn(true);
        $this->fs->read_file('userdata/bob/logins.json')->willReturn($file_content);

        $expected_rows = array_fill(0, 99, $test_row);
        $expected_rows[] = [
            'status' => 'failure',
            'time' => '2020-01-03T12:15:14-05:00',
            'ip' => '1.2.3.4',
            'user_agent' => 'Chrome'
        ];
        $expected_content = json_encode($expected_rows);
        $this->fs->write_file('userdata/bob/logins.json', $expected_content)
                 ->willReturn(1000)
                 ->shouldBeCalled();

        $log = AuthLog::failure(new DateTime('2020-01-03T12:15:14-05:00'), '1.2.3.4', 'Chrome');
        $limiter = $this->createLoginLimiter(new User('bob'));
        $limiter->logAttempt($log);
    }

    public function testLogAttempt_WhenLogHasItems_AppendsItem() {
        $this->setUpFileToReturn(
            'bob', [
            [
                'status' => 'success',
                'time' => '2020-01-02T12:13:14-05:00',
                'ip' => '1.2.3.4',
                'user_agent' => 'Chrome'
            ]
            ]
        );

        $expected_data = json_encode(
            [
            [
                'status' => 'success',
                'time' => '2020-01-02T12:13:14-05:00',
                'ip' => '1.2.3.4',
                'user_agent' => 'Chrome'
            ], [
                'status' => 'failure',
                'time' => '2020-01-03T13:14:15-05:00',
                'ip' => '2.3.4.5',
                'user_agent' => 'Firefox'
            ]
            ]
        );
        $this->fs->write_file('userdata/bob/logins.json', $expected_data)
                 ->willReturn(100)
                 ->shouldBeCalled();

        $log = AuthLog::failure(new DateTime('2020-01-03T13:14:15-05:00'), '2.3.4.5', 'Firefox');
        $limiter = $this->createLoginLimiter(new User('bob'));
        $limiter->logAttempt($log);
    }

    public function testLogAttempt_WhenWriteFails_Throws() {
        $this->globals->constant('USER_DATA_PATH')->willReturn('userdata');
        $this->fs->file_exists('userdata/bob/logins.json')->willReturn(true);
        $this->fs->read_file('userdata/bob/logins.json')->willReturn('');
        $this->fs->write_file('userdata/bob/logins.json', Argument::any())->willReturn(false);

        $this->expectException(FileWriteFailed::class);

        $log = AuthLog::success(new DateTime('2020-01-02T12:13:14-05:00'), '1.2.3.4', 'Chrome');
        $limiter = $this->createLoginLimiter(new User('bob'));
        $limiter->logAttempt($log);
    }

    public function testCanLogIn_WhenAttemptLimitExceeded_ReturnsFalse() {
        $this->setUpFileToReturn(
            'bob', [
            ['status' => 'failure', 'time' => '2020-01-03T13:09:21-05:00', 'ip' => '1.2.3.4', 'user_agent' => 'Chrome' ],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:15-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:16-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:17-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:18-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ]
        );
        $this->globals->sleep(Argument::any())->shouldBeCalled();
        $this->globals->time(Argument::any())->willReturn(strtotime('2020-01-03 13:14:20'));

        $limiter = $this->createLoginLimiter(new User('bob'));
        $result = $limiter->canLogIn();

        $this->assertFalse($result);
    }

    public function testCanLogIn_WhenAttemptHasFailures_DelaysByNumberOfFailures() {
        $this->setUpFileToReturn(
            'bob', [
            ['status' => 'success', 'time' => '2020-01-03T13:12:11-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:12:21-05:00', 'ip' => '1.2.3.4', 'user_agent' => 'Chrome' ],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:15-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:16-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ]
        );
        $this->globals->time(Argument::any())->willReturn(strtotime('2020-01-03 13:14:20'));

        $this->globals->sleep(6)->shouldBeCalled();

        $limiter = $this->createLoginLimiter(new User('bob'));
        $limiter->canLogIn();
    }

    public function testCanLogIn_WhenAttemptHasFailuresAndNoWait_DoesNotSleep() {
        $this->setUpFileToReturn(
            'bob', [
            ['status' => 'success', 'time' => '2020-01-03T13:12:11-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:12:21-05:00', 'ip' => '1.2.3.4', 'user_agent' => 'Chrome' ],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:15-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:16-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ]
        );
        $this->globals->time(Argument::any())->willReturn(strtotime('2020-01-03 13:14:20'));

        $this->globals->sleep(Argument::any())->shouldNotBeCalled();

        $limiter = $this->createLoginLimiter(new User('bob'));
        $limiter->canLogIn($wait = false);
    }

    public function testCanLogIn_WhenEnoughFailuresButNotInPeriod_ReturnsTrue() {
        $this->setUpFileToReturn(
            'bob', [
            ['status' => 'failure', 'time' => '2020-01-03T02:09:21-05:00', 'ip' => '1.2.3.4', 'user_agent' => 'Chrome' ],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:15-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:16-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:17-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:18-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ]
        );
        $this->globals->sleep(Argument::any())->shouldBeCalled();
        $this->globals->time(Argument::any())->willReturn(strtotime('2020-01-03 13:14:20'));

        $limiter = $this->createLoginLimiter(new User('bob'));
        $result = $limiter->canLogIn();

        $this->assertTrue($result);
    }

    public function testCanLogIn_WhenEnoughFailuresButAlsoSuccesses_ReturnsFalse() {
        $this->setUpFileToReturn(
            'bob', [
            ['status' => 'failure', 'time' => '2020-01-03T13:14:15-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:15-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:16-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'success', 'time' => '2020-01-03T13:14:16-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Chrome'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:17-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:18-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ]
        );
        $this->globals->sleep(Argument::any())->shouldBeCalled();
        $this->globals->time(Argument::any())->willReturn(strtotime('2020-01-03 13:14:20'));

        $limiter = $this->createLoginLimiter(new User('bob'));
        $result = $limiter->canLogIn();

        $this->assertFalse($result);
    }

    public function testCanLogIn_WhenFourFailuresThenCheckThenLogFifth_RetrunsFalse() {
        $this->globals->constant('USER_DATA_PATH')->willReturn('userdata');
        $this->globals->sleep(Argument::any())->shouldBeCalled();
        $this->fs->file_exists("userdata/bob/logins.json")->willReturn(true);
        $this->fs->read_file("userdata/bob/logins.json")->willReturn(
            json_encode(
                [
                ['status' => 'failure', 'time' => '2020-01-03T13:14:15-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
                ['status' => 'failure', 'time' => '2020-01-03T13:14:15-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
                ['status' => 'failure', 'time' => '2020-01-03T13:14:16-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
                ['status' => 'success', 'time' => '2020-01-03T13:14:16-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Chrome'],
                ['status' => 'failure', 'time' => '2020-01-03T13:14:17-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
                ]
            )
        );
        $this->globals->time(Argument::any())->willReturn(strtotime('2020-01-03 13:14:20'));

        $expected_content = json_encode(
            [
            ['status' => 'failure', 'time' => '2020-01-03T13:14:15-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:15-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:16-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'success', 'time' => '2020-01-03T13:14:16-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Chrome'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:17-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Firefox'],
            ['status' => 'failure', 'time' => '2020-01-03T13:14:20-05:00', 'ip' => '2.3.4.5', 'user_agent' => 'Chrome'],
            ]
        );
        $this->fs->write_file("userdata/bob/logins.json", $expected_content)->willReturn(100)->shouldBeCalled();

        $limiter = $this->createLoginLimiter(new User('bob'));
        $result1 = $limiter->canLogIn();
        $limiter->logAttempt(AuthLog::failure(new DateTime('2020-01-03 13:14:20'), '2.3.4.5', 'Chrome'));
        $result2 = $limiter->canLogIn();

        $this->assertTrue($result1);
        $this->assertFalse($result2);
    }

    protected function setUp(): void {
        Path::$sep = Path::UNIX_SEP;
        $this->tz = date_default_timezone_get();
        date_default_timezone_set("America/New_York");
        $this->prophet = new \Prophecy\Prophet();
        $this->fs = $this->prophet->prophesize(FS::class);
        $this->globals = $this->prophet->prophesize(GlobalFunctions::class);
    }

    protected function tearDown(): void {
        Path::$sep = DIRECTORY_SEPARATOR;
        date_default_timezone_set($this->tz);
        $this->prophet->checkPredictions();
    }

    private function createLoginLimiter(User $user) {
        return new LoginLimiter($user, $this->fs->reveal(), $this->globals->reveal());
    }

    private function setUpFileToReturn($user, $content) {
        $this->globals->constant('USER_DATA_PATH')->willReturn('userdata');
        $this->fs->file_exists("userdata/$user/logins.json")->willReturn(true);
        $this->fs->read_file("userdata/$user/logins.json")->willReturn(json_encode($content));
    }
}
