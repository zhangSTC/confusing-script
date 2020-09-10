<?php

namespace Confusing;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;

/**
 * 日志
 *
 * Class Log
 * @package Confusing
 */
class Log
{
    private static $logger;

    private static $requestId;

    private static function getLogger()
    {
        if (is_null(self::$logger)) {
            self::$logger = new Logger('app');
            $logFile = Util::getStoragePath() . '/log/' . date('Y-m-d') . '.log';
            self::$logger->pushHandler(new StreamHandler($logFile, Logger::INFO));
        }
        return self::$logger;
    }

    private static function getRequestId()
    {
        if (is_null(self::$requestId)) {
            self::$requestId = Uuid::uuid4()->toString();
        }
        return self::$requestId;
    }

    public static function info(string $message, array $context = [])
    {
        $context['id'] = self::getRequestId();
        self::getLogger()->info($message, $context);
    }

    public static function error(string $message, array $context = [])
    {
        $context['id'] = self::getRequestId();
        self::getLogger()->error($message, $context);
    }
}
