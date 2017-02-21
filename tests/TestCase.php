<?php

namespace RedisInAction\Test;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Predis\Client as RedisClient;

class TestCase extends PHPUnitTestCase
{
    protected $conn;

    protected function setUp()
    {
        $host = isset($GLOBALS['REDIS_HOST']) ? $GLOBALS['REDIS_HOST'] : '127.0.0.1';
        $port = isset($GLOBALS['REDIS_PORT']) ? $GLOBALS['REDIS_PORT'] : '6379';

        $this->conn = new RedisClient(['host' => $host, 'port' => $port]);
    }

    protected function tearDown()
    {
        unset($this->conn);
    }

    /**
     * Helper method to print message or data
     *
     * @see http://stackoverflow.com/a/12606210/1519894
     *
     * @author Sam Lu
     *
     * @param mixed $message
     */
    protected static function pprint($message = PHP_EOL)
    {
        fwrite(STDERR, trim(print_r($message, true), PHP_EOL) . PHP_EOL);
    }
}