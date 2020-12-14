<?php

class UrlPath
{
    private $path;
    private $url;

    public function __construct(string $path, string $url) {
        $this->path = $path;
        $this->url = $url;
    }

    public function path(): string {
        return $this->path;
    }

    public function url(): string {
        return $this->url;
    }

    public function toArray(): array {
        return [
            'path' => $this->path,
            'url' => $this->url,
        ];
    }

    public static function __set_state(array $data) {
        return new self($data['path'], $data['url']);
    }
}
