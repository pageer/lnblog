<?php

namespace LnBlog\User;

use DateTime;
use JsonSerializable;
use User;

class AuthLog implements JsonSerializable
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';

    private $status;
    private $time;
    private $ip;
    private $user_agent;

    public static function success(DateTime $time, $ip, $user_agent) {
        return new AuthLog(self::STATUS_SUCCESS, $time, $ip, $user_agent);
    }

    public static function failure(DateTime $time, $ip, $user_agent) {
        return new AuthLog(self::STATUS_FAILURE, $time, $ip, $user_agent);
    }

    public static function deserialize($item) {
        return new AuthLog(
            $item['status'],
            new DateTime($item['time']),
            $item['ip'],
            $item['user_agent']
        );
    }

    public function __construct($status, DateTime $time, $ip, $user_agent) {
        $this->status = $status;
        $this->time = $time;
        $this->ip = $ip;
        $this->user_agent = $user_agent;
    }

    public function status() {
        return $this->status;
    }

    public function time() {
        return $this->time;
    }

    public function ip() {
        return $this->ip;
    }

    public function userAgent() {
        return $this->user_agent;
    }

    public function jsonSerialize() {
        return [
            'status' => $this->status,
            'time' => $this->time->format("c"),
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
        ];
    }
}
