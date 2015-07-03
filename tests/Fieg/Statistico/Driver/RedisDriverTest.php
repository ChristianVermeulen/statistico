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
        $redis = $this->getMockBuilder('\Redis')
            ->getMock();

        $redis->expects($this->once())
            ->method('sAdd')
            ->with('buckets', 'some_bucket_name');

        $driver = new RedisDriver($redis);
        $driver->increment('some_bucket_name');
    }

    /**
     * @test
     */
    public function timing_adds_bucket_to_buckets_set()
    {
        /** @var \Redis|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->getMockBuilder('\Redis')
            ->getMock();

        $redis->expects($this->once())
            ->method('sAdd')
            ->with('buckets', 'some_bucket_name');

        $driver = new RedisDriver($redis);
        $driver->timing('some_bucket_name', 123);
    }

    /**
     * @test
     */
    public function gauge_adds_bucket_to_buckets_set()
    {
        /** @var \Redis|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->getMockBuilder('\Redis')
            ->getMock();

        $redis->expects($this->once())
            ->method('sAdd')
            ->with('buckets', 'some_bucket_name');

        $driver = new RedisDriver($redis);
        $driver->timing('some_bucket_name', 123);
    }

    /**
     * @test
     */
    public function increment_increments_by_one()
    {
        /** @var \Redis|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->getMockBuilder('\Redis')
            ->getMock();

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
}
