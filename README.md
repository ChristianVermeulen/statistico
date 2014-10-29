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

Tracking statistics:

```php
use Fieg\Statistico\Statistico;

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

Reading statistics:

```php
use Fieg\Statistico\Reader;

$redis = new \Redis();
$driver = new Fieg\Statistico\Driver\RedisDriver($redis);
$reader = new Reader($driver);

// query counts from 7 days ago to now
$counts = $reader->queryCounts('your.bucket.name', 'seconds', new \DateTime('-7 days'), new \DateTime());

// $counts would now contain an array of (unix) timestamps and counts
// [[10000000, 4], [10000001, 6], ...]

// query timings from 7 days ago to now
$timings = $reader->queryTimings('your.bucket.name', 'seconds', new \DateTime('-7 days'), new \DateTime());

// query gauges from 7 days ago to now
$gauges = $reader->queryGauges('your.bucket.name', 'seconds', new \DateTime('-7 days'), new \DateTime());
```

Available granularities are: seconds, minutes, hours and days.
