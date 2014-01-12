<?php

namespace Devster\Buzz\Listener;

use Buzz\Listener\ListenerInterface;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;

/**
 * WSSE signing plugin
 * @link http://www.xml.com/pub/a/2003/12/17/dive.html
 */
class WsseAuthListener implements ListenerInterface
{
    protected $username;
    protected $password;
    protected $nonceCallback;
    protected $timestampCallback;
    protected $digestCallback;

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        $this->setNonceCallback(function(RequestInterface $request) {
            return sha1(uniqid('', true) . $request->getUrl());
        });

        $this->setTimestampCallback(function(RequestInterface $request) {
            return date('c');
        });

        $this->setDigestCallback(function($nonce, $timestamp, $password, RequestInterface $request) {
            return base64_encode(sha1($nonce.$timestamp.$password, true));
        });
    }

    /**
     * Set the nonce callable that generate a unique nonce
     * The callable must return a string
     *
     * @param callable $callback
     * @return WsseAuthListener The current instance
     */
    public function setNonceCallback($callback)
    {
        $this->checkCallable($callback);
        $this->nonceCallback = $callback;

        return $this;
    }

    /**
     * Set the timestamp callable that generate a timestamp
     * The callable must return a string that follow the accepted format.
     * @link http://www.w3.org/TR/NOTE-datetime
     *
     * @param callable $callback
     * @return WsseAuthListener The current instance
     */
    public function setTimestampCallback($callback)
    {
        $this->checkCallable($callback);
        $this->timestampCallback = $callback;

        return $this;
    }

    /**
     * Set the digest callable that generate the digest
     * The callable must return a string base64 encoded.
     *
     * @param callable $callback
     * @return WsseAuthListener The current instance
     */
    public function setDigestCallback($callback)
    {
        $this->checkCallable($callback);
        $this->digestCallback = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function preSend(RequestInterface $request)
    {
        $nonce = call_user_func($this->nonceCallback, $request);
        $timestamp = call_user_func($this->timestampCallback, $request);
        $digest = call_user_func($this->digestCallback, $nonce, $timestamp, $this->password, $request);

        $request->addHeader('Authorization: WSSE profile="UsernameToken"');
        $request->addHeader(sprintf(
            'X-WSSE: UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $this->username,
            $digest,
            $nonce,
            $timestamp
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function postSend(RequestInterface $request, MessageInterface $response)
    {
    }

    /**
     * Helper.
     * Throw an \InvalidArgumentException if the var is not a callable
     */
    protected function checkCallable($callback)
    {
        if (! is_callable($callback)) {
            throw new \InvalidArgumentException('Callback must be a callable.');
        }
    }
}
