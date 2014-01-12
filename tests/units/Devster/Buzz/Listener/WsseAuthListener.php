<?php

namespace Tests\Units\Devster\Buzz\Listener;

use Devster\Buzz\Listener\WsseAuthListener as Wsse;
use mageekguy\atoum;

class WsseAuthListener extends atoum\test
{
    protected function createWsse()
    {
        return new Wsse('john', 'pass');
    }

    protected function createRequest()
    {
        return new \mock\Buzz\Message\Request;
    }

    /**
     * Return a protected property value
     *
     * @param  mixed $object
     * @param  string $property
     * @return mixed
     */
    protected function getProtectedValue($object, $property)
    {
        $class = new \ReflectionClass($object);
        $property = $class->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    public function testConstruct()
    {
        $w = new Wsse('john', 'pass');

        $this
            ->object($w)
                ->isInstanceOf('Buzz\Listener\ListenerInterface')
            ->string($this->getProtectedValue($w, 'username'))
                ->isEqualTo('john')
            ->string($this->getProtectedValue($w, 'password'))
                ->isEqualTo('pass')
        ;
    }

    public function testNonceCallback()
    {
        $w = $this->createWsse();
        $callback = $this->getProtectedValue($w, 'nonceCallback');

        $nonces = array();
        $request = $this->createRequest();

        for ($i = 0; $i < 1000; $i++) {
            $nonces[] = $callback($request);
        }

        $this
            ->array(array_unique($nonces))
                ->size
                    ->isEqualTo(1000)
            ->exception(function() use ($w) {
                $w->setNonceCallback('test');
            })
                ->isInstanceOf('\InvalidArgumentException')
            ->if($callback = function(){})
            ->object($w->setNonceCallback($callback))
                ->isEqualTo($w)
            ->variable($this->getProtectedValue($w, 'nonceCallback'))
            ->isEqualTo($callback)
            ->isNotEqualTo(function(){})
        ;
    }

    public function testTimestampCallback()
    {
        $w = $this->createWsse();
        $request = $this->createRequest();
        $callback = $this->getProtectedValue($w, 'timestampCallback');

        $this
            ->string($date = $callback($request))
            ->dateTime(new \DateTime($date))
            ->exception(function() use ($w) {
                $w->setTimestampCallback('test');
            })
                ->isInstanceOf('\InvalidArgumentException')
            ->if($callback = function(){})
            ->object($w->setTimestampCallback($callback))
                ->isEqualTo($w)
            ->variable($this->getProtectedValue($w, 'timestampCallback'))
            ->isEqualTo($callback)
            ->isNotEqualTo(function(){})
        ;
    }

    public function testDigestCallback()
    {
        $w = $this->createWsse();
        $request = $this->createRequest();
        $callback = $this->getProtectedValue($w, 'digestCallback');

        $nonce = 'unique';
        $timestamp = 'test';
        $password = 'pass';

        $this
            ->string($callback($nonce, $timestamp, $password, $request))
                ->isEqualTo($callback($nonce, $timestamp, $password, $request))
                ->isNotEqualTo($callback('other', $timestamp, $password, $request))
            ->exception(function() use ($w) {
                $w->setDigestCallback('test');
            })
                ->isInstanceOf('\InvalidArgumentException')
            ->if($callback = function(){})
            ->object($w->setDigestCallback($callback))
                ->isEqualTo($w)
            ->variable($this->getProtectedValue($w, 'digestCallback'))
            ->isEqualTo($callback)
            ->isNotEqualTo(function(){})
        ;
    }

    public function testPreSend()
    {
        $w = $this->createWsse();
        $request = $this->createRequest();

        $this
            ->variable($request->getHeader('Authorization'))
                ->isNull()
            ->variable($request->getHeader('X-WSSE'))
                ->isNull()
        ;

        $w->preSend($request);

        $this
            ->string($request->getHeader('Authorization'))
                ->isEqualTo('WSSE profile="UsernameToken"')
            ->string($header = $request->getHEader('X-WSSE'))
            ->integer(preg_match(
                '/UsernameToken Username="([^"]+)", PasswordDigest="([^"]+)", Nonce="([^"]+)", Created="([^"]+)"/',
                $header,
                $matches
            ))
                ->isEqualTo(1)
            ->string($matches[1])
                ->isEqualTo('john')
            ->if($digestCallback = $this->getProtectedValue($w, 'digestCallback'))
            ->string($matches[2])
                ->isEqualTo($digestCallback($matches[3], $matches[4], 'pass', $request))
            ->string($matches[3])
                ->isNotEmpty()
            ->dateTime(new \DateTime($matches[4]))
        ;
    }
}
