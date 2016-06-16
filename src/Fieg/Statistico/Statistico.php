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
     * @var Statistico
     */
    protected static $sharedInstance;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;

        static::$sharedInstance = $this;
    }

    /**
     * @param string $bucket
     * @param int $step
     *
     * @return mixed
     */
    public function increment($bucket, $step = 1)
    {
        $this->driver->increment($bucket, $step);
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
     * @param string   $bucket
     * @param callable $closure
     *
     * @return mixed
     */
    public function measure($bucket, callable $closure)
    {
        $start = round(microtime(true) * 1000);

        $retval = $closure();

        $stop = round(microtime(true) * 1000);

        $this->timing($bucket, $stop - $start);

        return $retval;
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

    /**
     * @return Statistico|NullStatistico
     */
    public static function sharedInstance()
    {
        if (null === static::$sharedInstance) {
            return new NullStatistico();
        }

        return static::$sharedInstance;
    }
}
