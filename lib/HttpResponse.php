<?php

class HttpResponse
{
    const RESPONSE_CODE_NOT_FOUND = 599;

    private $response_text = '';
    private $response_code = 0;
    private $headers = [];
    private $body = '';

    public function __construct($response_text) {
        $this->response_text = $response_text;
        $this->response_code = $this->extractResponseCode($response_text);
        $this->headers = $this->extractHeaders($response_text);
        $this->body = $this->extractBody($response_text);
    }

    public function rawResponse() {
        return $this->response_text;
    }

    public function body() {
        return $this->body;
    }

    public function header($name) {
        return isset($this->header[$name]) ? $this->header[$name] : '';
    }

    public function headers() {
        return $this->headers;
    }

    public function responseCode() {
        return $this->response_code;
    }

    private function extractResponseCode($response) {
        $first_line_end = strpos($response, "\n");
        $line = trim(substr($response, 0, $first_line_end));
        $pieces = explode(" ", $line);
        if (strpos($pieces[0], "HTTP/") === 0) {
            return (int)$pieces[1];
        }
        return self::RESPONSE_CODE_NOT_FOUND;
    }

    private function extractHeaders($response) {
        $header_break = strpos($response, "\r\n\r\n");
        if (! $header_break) {
            return [];
        }
        $response = substr($response, 0, $header_break);
        $lines = explode("\n", $response);
        $headers = [];
        foreach ($lines as $line) {
            $pieces = explode(":", $line, 2);
            if (count($pieces) == 2) {
                $name = trim($pieces[0]);
                $value = trim($pieces[1]);
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    private function extractBody($response) {
        $header_break = strpos($response, "\r\n\r\n");
        return substr($response, $header_break + 4);
    }
}
