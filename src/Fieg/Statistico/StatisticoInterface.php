<?php

namespace Fieg\Statistico;

interface StatisticoInterface
{
    /**
     * @param string $bucket
     */
    public function increment($bucket);

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
