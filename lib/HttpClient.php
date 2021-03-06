<?php

require_once __DIR__.'/../vendor/phpxmlrpc/phpxmlrpc/lib/xmlrpc.inc';
require_once __DIR__.'/../vendor/phpxmlrpc/phpxmlrpc/lib/xmlrpcs.inc';
require_once __DIR__.'/../vendor/phpxmlrpc/phpxmlrpc/lib/xmlrpc_wrappers.inc';

/**
 * Class: HttpClient
 * A simple class for making HTTP requests.
 *
 * @codeCoverageIgore
 */
class HttpClient
{
    # Method: fetchUrl
    # Fetch the content of  a URL.
    #
    # Parameters:
    # url - String containing the URL to fetch
    # header - Optional boolean switch for whether to include headers in the
    #          text.  Off by default.
    #
    # Returns:
    # The page content as a string.
    public function fetchUrl($url, $headers = false) {
        if (extension_loaded('curl')) {

            $hnd = curl_init();
            curl_setopt($hnd, CURLOPT_URL, $url);
            curl_setopt($hnd, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($hnd, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($hnd, CURLOPT_HEADER, 1);
            if ($headers) {
                curl_setopt($hnd, CURLOPT_NOBODY, 1);
            }
            $response = curl_exec($hnd);

        } else {

            $url_bits = parse_url($url);
            $host = $url_bits['host'];
            $path = isset($url_bits['path']) ? $url_bits['path'] : "/";
            $port = isset($url_bits['port']) ? $url_bits['port'] : 80;
            $query = isset($url_bits['query']) ? $url_bits['query'] : '';

            # Open a socket.
            $fp = @fsockopen($host, $port);
            if (!$fp) {
                return false;
            }

            # Create the HTTP request to be sent to the remote host.
            if ($query) {
                $path .= '?'.$query;
            }
            $method = $headers ? 'HEAD' : 'GET';
            $data = $method." ".$path."\r\n".
                    "Host: ".$host."\r\n".
                    "Connection: close\r\n\r\n";

            # Send the data and then get back any response.
            fwrite($fp, $data);
            $response = '';

            while (! feof($fp)) {
                $s = fgets($fp);
                $response .= $s;
            }
            fclose($fp);
        }
        return $response;
    }

    # Method: sendPost
    # Send a POST request and get the response.  The request is
    # sent as x-www-form-urlencoded data.
    #
    # Parameters:
    # url - The URL to which the resquest is sent.
    # data - The post data to send as a URL-encoded string.
    #
    # Returns:
    # An HttpResponse object containing the response.
    public function sendPost($url, $data) {
        if (extension_loaded("curl")) {

            # Initialize CURL and POST to the target URL.
            $hnd = curl_init();
            curl_setopt($hnd, CURLOPT_URL, $url);
            curl_setopt($hnd, CURLOPT_POST, 1);
            curl_setopt($hnd, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($hnd, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($hnd, CURLOPT_HEADER, 1);
            curl_setopt($hnd, CURLOPT_POSTFIELDS, $data);
            $response = curl_exec($hnd);

        } else {

            $url_bits = parse_url($url);
            $host = $url_bits['host'];
            $path = $url_bits['path'];
            $port = isset($url_bits['port']) ? $url_bits['port'] : 80;

            # Open a socket.
            $fp = pfsockopen($host, $port);
            if (!$fp) {
                return false;
            }

            # Create the HTTP request to be sent to the remote host.
            $data = "POST ".$path."\r\n".
                    "Host: ".$host."\r\n".
                    "Content-Type: application/x-www-form-urlencoded; ".
                    "charset=utf-8\r\n".
                    "Content-Length: ".strlen($data)."\r\n".
                    "Connection: close\r\n\r\n".
                  $data;

            # Send the data and then get back any response.
            fwrite($fp, $data);
            $response = '';

            while (! feof($fp)) {
                $response .= fgets($fp);
            }
            fclose($fp);
        }

        return new HttpResponse($response);
    }

    public function sendXmlRpcMessage($host, $path, $port, $msg) {
        $client = new xmlrpc_client($path, $host, $port);
        if (defined("XMLRPC_SEND_PING_DEBUG")) {
            $client->setDebug(1);
        }
        return $client->send($msg);
    }
}
