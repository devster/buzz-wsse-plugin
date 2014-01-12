buzz-wsse-plugin
================

[![Latest Stable Version](https://poser.pugx.org/devster/buzz-wsse-plugin/v/stable.png)](https://packagist.org/packages/devster/buzz-wsse-plugin) [![Build Status](https://travis-ci.org/devster/buzz-wsse-plugin.png?branch=master)](https://travis-ci.org/devster/buzz-wsse-plugin)

Buzz plugin to manage WSSE authentication

[Buzz](https://github.com/kriswallsmith/Buzz) is a lightweight PHP 5.3 library for issuing HTTP requests.

More informations on WSSE authentication [http://www.xml.com/pub/a/2003/12/17/dive.html](http://www.xml.com/pub/a/2003/12/17/dive.html)

Installation
------------

### Install via composer

```shell
# Install Composer
curl -sS https://getcomposer.org/installer | php

# Add the plugin as a dependency
php composer.phar require devster/buzz-wsse-plugin:~1.0
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

Basic usage
-----------

```php
require vendor/autoload.php

use Buzz\Browser;
use Devster\Buzz\Listener\WsseAuthListener;

// Create a Buzz client
$browser = new Browser();
// and add the Wsse listener
$browser->addListener(new WsseAuthListener('username', '*******'));

// finally use Buzz as usual
$response = $browser->get('http://www.google.com');

echo $browser->getLastRequest()."\n";
echo $response;
```

Customization
-------------

All the plugin is configurable: the way to generate the nonce, the timestamp and also the password digest.

```
use Buzz\Browser;
use Buzz\Message\RequestInterface;
use Devster\Buzz\Listener\WsseAuthListener;

$browser = new Buzz\Browser();
$wsse = new WsseAuthListener('bob', '*********');
$wsse
    // Customize the nonce generator
    // A callable that must return a string
    ->setNonceCallback(function(RequestInterface $request) {
        return uniqid('myapp_', true);
    })
    // Customize the timestamp generator
    // A callable that must return a string
    ->setTimestampCallback(function(RequestInterface $request) {
        $date = new \DateTime("now");
        return $date->format('c');
    })
    // Customize the digest generator
    // A callable that must return a string
    ->setDigestCallback(function($nonce, $timestamp, $password, RequestInterface $request) {
        return hash('sha512', $nonce.$timestamp.$password, true);
    })
;

// add the listener to the browser
$browser->addListener($wsse);
```

Tests
-----

Unit tests are made with atoum.

```shell
composer install --dev

./vendor/bin/atoum
```

License
-------

This plugin is licensed under the MIT License