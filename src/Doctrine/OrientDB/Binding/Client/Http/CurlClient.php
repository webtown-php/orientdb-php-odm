<?php

/*
 * This file is part of the Doctrine\OrientDB package.
 *
 * (c) Alessandro Nadalin <alessandro.nadalin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This class represents an HTTP client based on Curl.
 *
 * @package    Doctrine\OrientDB
 * @subpackage Binding
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     Daniele Alessandri <suppakilla@gmail.com>
 */

namespace Doctrine\OrientDB\Binding\Client\Http;

use Doctrine\OrientDB\Binding\Exception\ConnectionFailedException;

class CurlClient
{
    protected $curl;
    protected $restart;
    protected $cookies;
    protected $authentication;

    /**
     * Creates a new HTTP client based on cURL.
     *
     * @param boolean $restart
     * @param integer $timeout
     */
    public function __construct($restart = false, $timeout = 10) {
        $this->restart = $restart;
        $this->cookies = [];
        $this->curl    = $this->createCurlHandle();

        $this->setTimeout($timeout);
    }

    /**
     * Closes the underlying cURL handle.
     */
    public function __destruct() {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
    }

    /**
     * Sets a timeout for the current cURL handler's requests.
     *
     * @param integer $timeout
     */
    public function setTimeout($timeout) {
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * Returns a string with the list of cookies for the Cookie header.
     *
     * @return string
     */
    protected function getRequestCookies() {
        $pairs = [];

        foreach ($this->cookies as $k => $v) {
            $pairs[] = "$k=$v";
        }

        return join($pairs, ';');
    }

    /**
     * Executes a Curl.
     *
     * @param  String $method
     * @param  String $location
     *
     * @return CurlClientResponse
     * @throws ConnectionFailedException
     */
    public function execute($method, $location) {
        curl_setopt_array($this->curl, [
            CURLOPT_URL           => $location,
            CURLOPT_COOKIE        => $this->getRequestCookies(),
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if (!$response = curl_exec($this->curl)) {
            $err = curl_error($this->curl);
            $msg = sprintf("unable to communicate with server at '%s'; %s", $location, $err);
            $this->restart();
            throw new ConnectionFailedException($msg);
        }

        $response      = new CurlClientResponse($response);
        $this->cookies = array_merge($this->cookies, $response->getCookies());

        if ($this->restart === true) {
            $this->restart();
        }

        return $response;
    }

    /**
     * Executes a DELETE on a resource.
     *
     * @param  String $location
     *
     * @param null    $body
     *
     * @return CurlClientResponse
     * @throws ConnectionFailedException
     */
    public function delete($location, $body = null) {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");

        if ($body) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
        }

        return $this->execute('DELETE', $location);
    }

    public function head($location) {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'HEAD');

        return $this->execute('HEAD', $location);
    }

    /**
     * GETs a resource.
     *
     * @param  String $location
     *
     * @return CurlClientResponse
     */
    public function get($location) {
        curl_setopt($this->curl, CURLOPT_HTTPGET, true);

        return $this->execute('GET', $location);
    }

    /**
     * Executes a POST on a location.
     *
     * @param  String $location
     * @param  String $body
     *
     * @return CurlClientResponse
     */
    public function post($location, $body) {
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        return $this->execute('POST', $location);
    }

    /**
     * PUTs a resource.
     *
     * @param  String $location
     * @param  String $body
     *
     * @return CurlClientResponse
     */
    public function put($location, $body) {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        return $this->execute('PUT', $location);
    }

    /**
     * Sets the authentication string for the next HTTP requests.
     *
     * @param String $credentials
     */
    public function setAuthentication($credentials) {
        $this->authentication = $credentials;
        curl_setopt($this->curl, CURLOPT_USERPWD, $credentials);
    }

    /**
     * Sets an HTTP header to send within the request.
     *
     * @param string $header
     * @param mixed  $value
     */
    public function setHeader($header, $value) {
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, ["$header: $value"]);
    }

    /**
     * Reinitializes the client for a completely new session.
     */
    public function restart() {
        curl_close($this->curl);

        $this->cookies = [];
        $this->curl    = $this->createCurlHandle();
    }

    /**
     * Returns an array with a set of default options for cURL.
     *
     * @return array
     */
    protected function getDefaultCurlOptions() {
        return [
            CURLOPT_HEADER         => true,
            CURLOPT_RETURNTRANSFER => true,
        ];
    }

    /**
     * Creates and initializes the underlying cURL handle.
     *
     * @return resource
     */
    protected function createCurlHandle() {
        $options = $this->getDefaultCurlOptions();

        if (isset($this->authentication)) {
            $options[CURLOPT_USERPWD] = $this->authentication;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $options);

        return $curl;
    }
}
