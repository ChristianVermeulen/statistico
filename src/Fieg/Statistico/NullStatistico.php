<?php

namespace Fieg\Statistico;

class NullStatistico implements StatisticoInterface
{
    /**
     * @param string $bucket
     */
    public function increment($bucket)
    {
        // noop
    }

    /**
     * @param string $bucket
     * @param integer $time time in ms
     */
    public function timing($bucket, $time)
    {
        // noop
    }

    /**
     * @param string $bucket
     * @param float|int $value
     */
    public function gauge($bucket, $value)
    {
        // noop
    }

    /**
     * @param string   $bucket
     * @param callable $closure
     *
     * @return mixed
     */
    public function measure($bucket, \Closure $closure)
    {
        $retval = $closure();

        return $retval;
    }
}
