<?php

namespace Fieg\Statistico\Driver;

class RedisDriver implements DriverInterface
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @param \Redis $redis
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $bucket
     *
     * @return mixed
     */
    public function increment($bucket)
    {
        $this->redis->hIncrBy('counts.' . $bucket, $this->syncedTime(),  1);
        $this->redis->sAdd('buckets', $bucket);
    }

    /**
     * @param string  $bucket
     * @param integer $time   time in ms
     *
     * @return mixed
     */
    public function timing($bucket, $time)
    {
        $this->redis->hSetNx('timings.' . $bucket, $this->syncedTime(),  $time);
        $this->redis->sAdd('buckets', $bucket);
    }

    /**
     * @param string $bucket
     * @param float  $value
     *
     * @return mixed
     */
    public function gauge($bucket, $value)
    {
        // Not implemented yet
    }

    /**
     * @param string $bucket
     *
     * @return array
     */
    public function export($bucket)
    {
        $counts = $this->redis->hGetAll('counts.' . $bucket);
        $timings = $this->redis->hGetAll('timings.' . $bucket);

        return [
            'counts'  => $counts,
            'timings' => $timings,
            'gauges'  => [],
        ];
    }

    /**
     * @return array
     */
    public function buckets()
    {
        return (array) $this->redis->sMembers('buckets');
    }

    /**
     * @return int
     */
    protected function syncedTime()
    {
        $now = time();
        list($redisTime) = $this->redis->time();

        $diff = $redisTime - $now;

        return $now + $diff;
    }
}
