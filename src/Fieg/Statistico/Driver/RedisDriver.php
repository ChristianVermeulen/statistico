<?php

namespace Fieg\Statistico\Driver;

use string;

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

        $prefix = $this->redis->getOption(\Redis::OPT_PREFIX);

        $granularities = $this->getGranularities();
        $settings = $granularities[$granularity];

        $keys = $this->redis->keys(sprintf('%s:%s:%s:*', $bucket, $type, $granularity));

        $data = [];

        foreach ($keys as $key) {
            $key = substr($key, strlen($prefix));

            list (,,, $time) = explode(':', $key);

            $endTime = $time + ($settings['partition'] * $settings['factor']);

            if ($from->getTimestamp() >= $time || $to->getTimestamp() <= $endTime) {
                $all = $this->redis->hGetAll($key);

                foreach ($all as $stamp => $value) {
                    if ($stamp >= $from->getTimestamp() && $stamp <= $to->getTimestamp()) {
                        $data[$stamp] = (int) $value;
                    }
                }
            }
        }

        ksort($data);

        if ($type === 'counts') {
            $data = $this->completeCountsData($data, $granularities[$granularity]['factor'], $from, $to);
        }

        return $data;
    }

    /**
     * @return array
     */
    public function buckets()
    {
        return (array) $this->redis->sMembers('buckets');
    }

    /**
     * @param $bucket
     *
     * @return string[]
     */
    public function types($bucket)
    {
        $prefix = $this->redis->getOption(\Redis::OPT_PREFIX);

        $keys = $this->redis->keys(sprintf('%s:*', $bucket));

        $types = [];

        foreach ($keys as $key) {
            $key = substr($key, strlen($prefix));

            list (,$type) = explode(':', $key);

            $types[] = $type;
        }

        return array_unique($types);
    }

    /**
     * @param array     $data
     * @param int       $factor
     * @param \DateTime $from
     * @param \DateTime $to
     *
     * @return array
     */
    protected function completeCountsData(array $data, $factor, \DateTime $from, \DateTime $to = null)
    {
        reset($data);

        // factor diff
        $mod = ($from->getTimestamp() % $factor);

        $min = max($from->getTimestamp() - $mod, key($data)); // first key
        $max = $to->getTimestamp();

        $retval = [];

        for ($t = $min; $t <= $max; $t+= $factor) {
            $retval[$t] = isset($data[$t]) ? $data[$t] : 0;
        }

        return $retval;
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
     * @param string $bucket
     * @param string $type        counts, timings, etc.
     * @param string $granularity
     * @param array  $settings
     *
     * @return string
     */
    protected function getKey($bucket, $type, $granularity, array $settings)
    {
        $factor = $settings['partition'] * $settings['factor'];
        $time = floor($this->syncedTime() / $factor) * $factor;

        return sprintf('%s:%s:%s:%s', $bucket, $type, $granularity, $time);
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
}
