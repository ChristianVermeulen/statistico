<?php

namespace Fieg\Statistico\Driver;

use Fieg\Statistico\StatisticoInterface;

Interface DriverInterface extends StatisticoInterface
{
    /**
     * @param string    $bucket
     * @param string    $type
     * @param string    $granularity
     * @param \DateTime $from
     * @param \DateTime $to
     *
     * @return array
     */
    public function export($bucket, $type, $granularity, \DateTime $from, \DateTime $to = null);

    /**
     * @return string[]
     */
    public function buckets();

    /**
     * @param string $bucket
     *
     * @return string[]
     */
    public function types($bucket);
}
