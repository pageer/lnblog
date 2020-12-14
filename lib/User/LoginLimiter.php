<?php

namespace LnBlog\User;

use DateTime;
use DateTimeZone;
use FileWriteFailed;
use FS;
use GlobalFunctions;
use Path;
use User;

class LoginLimiter
{

    const ATTEMPT_LIMIT = 5;
    const TIME_LIMIT = 300;
    const DELAY_PER_FAILURE = 2;
    const MAX_LOG_SIZE = 100;

    private $user;
    private $fs;
    private $globals;
    private $user_auth_logs = [];

    public function __construct(User $user, FS $fs, GlobalFunctions $globals) {
        $this->user = $user;
        $this->fs = $fs;
        $this->globals = $globals;
    }

    public function canLogIn($wait = true) {
        $log = $this->getLoginAttempts();
        $curr_time = $this->globals->time($use_datetime = true);
        $filter = function ($item) use ($curr_time) {
            $time_diff = $curr_time - $item->time()->getTimestamp();
            return $item->status() === AuthLog::STATUS_FAILURE &&
                $time_diff < self::TIME_LIMIT;
        };
        $recent_failures = array_filter($log, $filter);

        $failure_count = count($recent_failures);
        if ($failure_count && $wait) {
            $this->globals->sleep(self::DELAY_PER_FAILURE * $failure_count);
        }

        return $failure_count < self::ATTEMPT_LIMIT;
    }

    public function logAttempt(AuthLog $log) {
        $logs = $this->getLoginAttempts();
        $logs[] = $log;
        $logs = array_slice($logs, -1 * self::MAX_LOG_SIZE);
        $this->writeAuthLog($logs);
    }

    public function getLoginAttempts() {
        if (!empty($this->user_auth_logs)) {
            return $this->user_auth_logs;
        }

        $log_file = $this->getUserLog();

        if (!$this->fs->file_exists($log_file)) {
            return [];
        }

        $content = $this->fs->read_file($log_file);
        $logins = json_decode($content, true) ?: [];

        $this->user_auth_logs = array_map(
            function ($item) {
            return AuthLog::deserialize($item);
            }, $logins
        );

        return $this->user_auth_logs;
    }

    private function writeAuthLog($logs) {
        $this->user_auth_logs = $logs;
        $content = json_encode($logs);
        $result = $this->fs->write_file($this->getUserLog(), $content);
        if (!$result) {
            throw new FileWriteFailed();
        }
    }

    private function getUserLog() {
        return Path::mk(
            $this->globals->constant('USER_DATA_PATH'),
            $this->user->username(),
            'logins.json'
        );
    }
}
