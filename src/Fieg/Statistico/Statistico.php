<?php

namespace Fieg\Statistico;

use Fieg\Statistico\Driver\DriverInterface;

class Statistico implements StatisticoInterface
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param string $bucket
     *
     * @return mixed
     */
    public function increment($bucket)
    {
        $this->driver->increment($bucket);
    }

    /**
     * @param string  $bucket
     * @param integer $time   time in ms
     *
     * @return mixed
     */
    public function timing($bucket, $time)
    {
        $this->driver->timing($bucket, $time);
    }

    /**
     * @param string $bucket
     * @param float  $value
     *
     * @return mixed
     */
    public function gauge($bucket, $value)
    {
        $this->driver->gauge($bucket, $value);
    }
}
