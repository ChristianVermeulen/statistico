<?php

namespace Fieg\Statistico;

use Fieg\Statistico\Driver\DriverInterface;

class Reader
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Constructor.
     *
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @return string[]
     */
    public function getBuckets()
    {
        return $this->driver->buckets();
    }

    /**
     * @return string[]
     */
    public function getAvailableTypes($bucket)
    {
        return $this->driver->types($bucket);
    }

    /**
     * @param string    $bucket
     * @param string    $granularity
     * @param \DateTime $from
     * @param \DateTime $end
     *
     * @return array
     */
    public function queryCounts($bucket, $granularity, \DateTime $from, \DateTime $end = null)
    {
        $counts = $this->driver->export($bucket, 'counts', $granularity, $from, $end);

        return $counts;
    }

    /**
     * @param string    $bucket
     * @param string    $granularity
     * @param \DateTime $from
     * @param \DateTime $end
     *
     * @return array
     */
    public function queryTimings($bucket, $granularity, \DateTime $from, \DateTime $end = null)
    {
        $timings = $this->driver->export($bucket, 'timings', $granularity, $from, $end);

        return $timings;
    }
}
