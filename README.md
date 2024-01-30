# clue/reactphp-ssdp

[![CI status](https://github.com/clue/reactphp-ssdp/actions/workflows/ci.yml/badge.svg)](https://github.com/clue/reactphp-ssdp/actions)
[![installs on Packagist](https://img.shields.io/packagist/dt/clue/ssdp-react?color=blue&label=installs%20on%20Packagist)](https://packagist.org/packages/clue/ssdp-react)

Async [Simple Service Discovery Protocol (SSDP)](https://en.wikipedia.org/wiki/Simple_Service_Discovery_Protocol), built on top of [ReactPHP](https://reactphp.org/).

SSDP is part of [Universal Plug and Play (UPnP)](https://de.wikipedia.org/wiki/Universal_Plug_and_Play)
and is commonly used to discover network services on home networks without
requiring any manual configuration, such as automatically discovering printers,
multimedia devices and network routers.

This library implements SSDP as defined in the
[UPnP device architecture definition](http://upnp.org/specs/arch/UPnP-arch-DeviceArchitecture-v1.1.pdf) (PDF)
and uses HTTP over Multicast UDP (HTTPMU) as defined in the
[IETF draft](https://datatracker.ietf.org/doc/html/draft-goland-http-udp-01).
As an alternative, some devices use DNS-Based Service Discovery (DNS-SD) instead
as defined in [RFC 6763](https://tools.ietf.org/html/rfc6763).

**Table of Contents**

* [Quickstart example](#quickstart-example)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

> Note: This project is in early alpha stage! Feel free to report any issues you encounter.

## Quickstart example

Once [installed](#install), you can use the following code to search all available UPnP devices in your network:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$client = new Clue\React\Ssdp\Client();

$client->search()->then(
    function () {
        echo 'Search completed' . PHP_EOL;
    },
    function(Exception $e) {
        echo 'There was an error searching for devices: ' . $e . PHP_EOL;
    },
    function ($progress) {
        echo 'Found a device: ' . PHP_EOL;
        var_dump($progress);
        echo PHP_EOL;
    }
);
```

See also the [examples](examples/).

## Install

The recommended way to install this library is [through composer](https://getcomposer.org/). [New to composer?](https://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/ssdp-react": "dev-master"
    }
}
```

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 8+ and
HHVM.
It's *highly recommended to use the latest supported PHP version* for this project.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org/):

```bash
composer install
```

To run the test suite, go to the project root and run:

```bash
vendor/bin/phpunit
```

## License

MIT
