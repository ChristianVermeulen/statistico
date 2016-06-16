<?php

namespace Fieg\Statistico;

interface StatisticoInterface
{
    /**
     * @param string $bucket
     * @param $step
     *
     * @return
     */
    public function increment($bucket, $step = 1);

    /**
     * @param string  $bucket
     * @param integer $time   time in ms
     */
    public function timing($bucket, $time);

    /**
     * @param string    $bucket
     * @param float|int $value
     */
    public function gauge($bucket, $value);
}
