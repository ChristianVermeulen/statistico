<?php

namespace Fieg\Statistico\Tests;

use Fieg\Statistico\Driver\RedisDriver;

class RedisDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function increment_adds_bucket_to_buckets_set()
    {
        /** @var \Redis|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->createMock(\Redis::class);

        $redis->expects($this->exactly(2))
            ->method('sAdd')
            ->withConsecutive(
                ['buckets', 'some_bucket_name'],
                ['types:some_bucket_name', 'counts']
            )
        ;

        $driver = new RedisDriver($redis);
        $driver->increment('some_bucket_name');
    }

    /**
     * @test
     */
    public function timing_adds_bucket_to_buckets_set()
    {
        /** @var \Redis|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->createMock(\Redis::class);

        $redis->expects($this->exactly(2))
              ->method('sAdd')
              ->withConsecutive(
                  ['buckets', 'some_bucket_name'],
                  ['types:some_bucket_name', 'timings']
              )
        ;

        $driver = new RedisDriver($redis);
        $driver->timing('some_bucket_name', 123);
    }

    /**
     * @test
     */
    public function gauge_adds_bucket_to_buckets_set()
    {
        /** @var \Redis|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->createMock(\Redis::class);

        $redis->expects($this->exactly(2))
              ->method('sAdd')
              ->withConsecutive(
                  ['buckets', 'some_bucket_name'],
                  ['types:some_bucket_name', 'gauges']
              )
        ;

        $driver = new RedisDriver($redis);
        $driver->gauge('some_bucket_name', 123);
    }

    /**
     * @test
     */
    public function increment_increments_by_one()
    {
        /** @var \Redis|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->createMock(\Redis::class);

        $redis->expects($this->exactly(4))
            ->method('hIncrBy')
            ->withConsecutive(
                [$this->matchesRegularExpression('/^some_bucket_name:counts:seconds:\d+$/'), $this->isType('float'), 1],
                [$this->matchesRegularExpression('/^some_bucket_name:counts:minutes:\d+$/'), $this->isType('float'), 1],
                [$this->matchesRegularExpression('/^some_bucket_name:counts:hours:\d+$/'), $this->isType('float'), 1],
                [$this->matchesRegularExpression('/^some_bucket_name:counts:days:\d+$/'), $this->isType('float'), 1]
            );

        $driver = new RedisDriver($redis);
        $driver->increment('some_bucket_name');
    }

    /**
     * @test
     */
    public function increment_increments_by_more_than_one()
    {
        /** @var \Redis|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->createMock(\Redis::class);

        $redis->expects($this->exactly(4))
            ->method('hIncrBy')
            ->withConsecutive(
                [$this->matchesRegularExpression('/^some_bucket_name:counts:seconds:\d+$/'), $this->isType('float'), 4],
                [$this->matchesRegularExpression('/^some_bucket_name:counts:minutes:\d+$/'), $this->isType('float'), 4],
                [$this->matchesRegularExpression('/^some_bucket_name:counts:hours:\d+$/'), $this->isType('float'), 4],
                [$this->matchesRegularExpression('/^some_bucket_name:counts:days:\d+$/'), $this->isType('float'), 4]
            );

        $driver = new RedisDriver($redis);
        $driver->increment('some_bucket_name', 4);
    }
}
