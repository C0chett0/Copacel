<?php


namespace C0chett0\CopacelEws;

use function getenv;
use jamesiarmes\PhpEws\Client as BaseClient;

class Client
{
    private static $instance;

    private function __construct() {}

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        if (! self::$instance) {
            return new BaseClient(
                getenv('SERVER'),
                getenv('USERNAME'),
                getenv('PASSWORD'),
                getenv('VERSION')
            );
        }

        return self::$instance;
    }
}