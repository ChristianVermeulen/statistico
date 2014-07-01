<?php

namespace Fieg\Statistico\Driver;

use Fieg\Statistico\StatisticoInterface;

Interface DriverInterface extends StatisticoInterface
{
    /**
     * @param string $bucket
     *
     * @return array
     */
    public function export($bucket);

    /**
     * @return string[]
     */
    public function buckets();
}
