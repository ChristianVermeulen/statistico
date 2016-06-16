<?php

namespace Fieg\Statistico;

class NullStatistico implements StatisticoInterface
{
    /**
     * @inheritdoc
     */
    public function increment($bucket, $step = 1)
    {
        // noop
    }

    /**
     * @inheritdoc
     */
    public function timing($bucket, $time)
    {
        // noop
    }

    /**
     * @inheritdoc
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
    public function measure($bucket, callable $closure)
    {
        $retval = $closure();

        return $retval;
    }
}
