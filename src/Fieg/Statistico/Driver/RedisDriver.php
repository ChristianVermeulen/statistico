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
     * @see http://blog.apiaxle.com/post/storing-near-realtime-stats-in-redis/
     *
     * @param string $bucket
     *
     * @return mixed
     */
    public function increment($bucket)
    {
        $granularities = $this->getGranularities();

        foreach ($granularities as $granularity => $settings) {
            $key   = $this->getKey($bucket, 'counts', $granularity, $settings);
            $field = $this->getField($bucket, 'counts', $granularity, $settings);

            $this->redis->hIncrBy($key, $field, 1);
            $this->redis->expireAt($key, $this->syncedTime() + $settings['ttl']);
        }

        $this->redis->sAdd('buckets', $bucket);
        $this->redis->sAdd(sprintf('types:%s', $bucket), 'counts');
    }

    /**
     * @param string  $bucket
     * @param integer $time   time in ms
     *
     * @return mixed
     */
    public function timing($bucket, $time)
    {
        $granularities = $this->getGranularities();

        foreach ($granularities as $granularity => $settings) {
            $key   = $this->getKey($bucket, 'timings', $granularity, $settings);
            $field = $this->getField($bucket, 'timings', $granularity, $settings);

            $this->redis->hSetNx($key, $field, $time);
            $this->redis->expireAt($key, $this->syncedTime() + $settings['ttl']);
        }

        $this->redis->sAdd('buckets', $bucket);
        $this->redis->sAdd(sprintf('types:%s', $bucket), 'timings');
    }

    /**
     * @param string $bucket
     * @param float  $value
     *
     * @return mixed
     */
    public function gauge($bucket, $value)
    {
        $granularities = $this->getGranularities();

        foreach ($granularities as $granularity => $settings) {
            $key   = $this->getKey($bucket, 'gauges', $granularity, $settings);
            $field = $this->getField($bucket, 'gauges', $granularity, $settings);

            $this->redis->hSet($key, $field, $value);
            $this->redis->expireAt($key, $this->syncedTime() + $settings['ttl']);
        }

        $this->redis->sAdd('buckets', $bucket);
        $this->redis->sAdd(sprintf('types:%s', $bucket), 'gauges');
    }

    /**
     * @param string    $bucket
     * @param string    $type
     * @param string    $granularity
     * @param \DateTime $from
     * @param \DateTime $to
     *
     * @return array
     */
    public function export($bucket, $type, $granularity, \DateTime $from, \DateTime $to = null)
    {
        if (null === $to) {
            $to = new \DateTime();
        }

        $granularities = $this->getGranularities();
        $settings = $granularities[$granularity];

        $keys = $this->getKeysForRange($bucket, $type, $granularity, $settings, $from, $to);

        $data = [];

        foreach ($keys as $key) {
            $all = $this->redis->hGetAll($key);

            foreach ($all as $stamp => $value) {
                if ($stamp >= $from->getTimestamp() && $stamp <= $to->getTimestamp()) {
                    $data[$stamp] = (int) $value;
                }
            }
        }

        ksort($data);

        return $data;
    }

    /**
     * @return string[]
     */
    public function buckets()
    {
        return (array) $this->redis->sMembers('buckets');
    }

    /**
     * @param string $bucket
     *
     * @return string[]
     */
    public function types($bucket)
    {
        return (array) $this->redis->sMembers('types:' . $bucket);
    }

    /**
     * @return int
     */
    protected function syncedTime()
    {
        return $this->redis->time()[0];
    }

    /**
     * @return array
     */
    protected function getGranularities()
    {
        $granularities = [
            'seconds' => [
                'partition' => 3600,             # A single partition stores 3600 records (1 hour)
                'ttl'       => 60 * 60 * 24,     # Each partition is kept for 24 hours
                'factor'    => 1,                # A second consists of 1 second
            ],
            'minutes' => [
                'partition' => 60 * 24,          # A single partition stores 1440 minutes (1 day)
                'ttl'       => 60 * 60 * 24 * 7, # Each partition kept for 7 days
                'factor'    => 60,               # A minute consists out of 60 seconds
            ],
            'hours' => [
                'partition' => 24,               # A single partition stores 24 hours (1 day)
                'ttl'       => 60 * 60 * 24 * 7, # Each partition kept for 7 days
                'factor'    => 3600,             # An hour consists out of 3600 seconds
            ],
            'days' => [
                'partition' => 365,              # A single partition stores 365 days (1 year)
                'ttl'       => 86400 * 365 * 5,  # Kept for 5 years
                'factor'    => 86400,            # A day consists out of 86400 seconds
            ],
        ];

        return $granularities;
    }

    /**
     * @param int $time
     * @param array $settings
     *
     * @return int
     */
    protected function getRoundedTime($time, array $settings)
    {
        $factor = $settings['partition'] * $settings['factor'];
        $roundedTime = floor($time / $factor) * $factor;

        return $roundedTime;
    }

    /**
     * @param string $bucket
     * @param string $type counts, timings, etc.
     * @param string $granularity
     * @param array $settings
     * @param null|int $time
     *
     * @return string
     */
    protected function getKey($bucket, $type, $granularity, array $settings, $time = null)
    {
        $time = $time ?: $this->syncedTime();

        $roundedTime = $this->getRoundedTime($time, $settings);

        return sprintf('%s:%s:%s:%s', $bucket, $type, $granularity, $roundedTime);
    }

    /**
     * @param string $bucket
     * @param string $type        counts, timings, etc.
     * @param string $granularity
     * @param array  $settings
     *
     * @return string
     */
    protected function getField($bucket, $type, $granularity, array $settings)
    {
        $factor = $settings['factor'];

        return floor($this->syncedTime() / $factor) * $factor;
    }

    /**
     * @param string $bucket
     * @param string $type
     * @param string $granularity
     * @param array $settings
     * @param \DateTime $from
     * @param \DateTime $to
     *
     * @return string[]
     */
    protected function getKeysForRange($bucket, $type, $granularity, array $settings, \DateTime $from, \DateTime $to)
    {
        $keys = [];

        $i = $from->getTimestamp();
        $y = $to->getTimestamp();

        while ($i < $y) {
            $keys[$this->getKey($bucket, $type, $granularity, $settings, $i)] = 1;

            $i += $settings['factor'];
        }

        return array_keys($keys);
    }
}
