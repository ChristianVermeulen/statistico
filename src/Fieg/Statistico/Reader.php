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
     * @param  string    $bucket
     * @param  \DateTime $from
     * @param  \DateTime $end
     * @return array
     */
    public function queryCounts($bucket, \DateTime $from, \DateTime $end = null)
    {
        if (null === $end) {
            $end = new \DateTime();
        }

        $data = $this->driver->export($bucket);

        $counts = $data['counts'];

        $retval = [];

        foreach ($counts as $time => $count) {
            if ($time > $from->getTimestamp() && $time < $end->getTimestamp()) {
                $retval[$time] = $count;
            }
        }

        return $retval;
    }

    /**
     * @param  string    $bucket
     * @param  \DateTime $end
     * @return float|int
     */
    public function queryRPM($bucket, \DateTime $end = null)
    {
        $interval = 20;

        if (null === $end) {
            $end = new \DateTime();
        }

        $from = clone $end;
        $from->sub(new \DateInterval('PT'.$interval.'S'));

        $data = $this->driver->export($bucket);

        $counts = $data['counts'];

        $retval = 0;

        foreach ($counts as $time => $count) {
            if ($time > $from->getTimestamp() && $time < $end->getTimestamp()) {
                $retval += (int) $count;
            }
        }

        $retval = floor($retval * 60 / $interval);

        return $retval;
    }

    /**
     * @param  string    $bucket
     * @param  \DateTime $from
     * @param  \DateTime $end
     * @return array
     */
    public function queryAllRPM($bucket, \DateTime $from, \DateTime $end = null)
    {
        if (null === $end) {
            $end = new \DateTime();
        }

        $min = $from->getTimestamp();
        $max = $end->getTimestamp();

        $retval = [];

        $i = 0;

        for ($t = $min; $t <= $max; $t++) {
            $time = new \DateTime('@'.$t);
            $rpm = $this->queryRPM($bucket, $time);

            $retval[$t] = $rpm;

            $i++;
        }

        return $retval;
    }
}
