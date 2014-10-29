Statistico
==========

Library to track statistics.

[![Build Status](https://travis-ci.org/fieg/statistico.png?branch=master)](https://travis-ci.org/fieg/statistico)

Installation
------------

Using composer:

```sh
composer require fieg/statistico:dev-master
```

Usage
-----

```php
$redis = new \Redis();
$driver = new Fieg\Statistico\Driver\RedisDriver($redis);
$statistico = new Statistico($driver);

// increment
$statistico->increment('your.bucket.name'); // increments with 1

// gauge
$statistico->gauge('your.bucket.name', 500); // sets bucket value to 500

// timing
$statistico->timing('your.bucket.name', 300); // sets bucket value to 300ms
```
