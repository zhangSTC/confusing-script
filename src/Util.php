<?php

namespace Confusing;

use Elasticsearch\Client as EsClient;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;
use Redis;

/**
 * 工具
 *
 * Class Util
 * @package Confusing
 */
class Util
{
    private static ?Redis $redisClient = null;

    private static ?Client $httpClient = null;

    private static ?EsClient $esClient = null;

    /**
     * 获取存储目录的绝对路径
     *
     * @return string
     */
    public static function getStoragePath(): string
    {
        return dirname(__DIR__) . '/storage';
    }

    /**
     * 获取Redis连接
     *
     * @param string $host
     * @param int $port
     * @param int $timeout
     * @return Redis
     */
    public static function getRedisClient(string $host = '127.0.0.1', int $port = 6379, int $timeout = 5): Redis
    {
        if (is_null(self::$redisClient)) {
            self::$redisClient = new Redis();
            self::$redisClient->connect($host, $port, $timeout);
        }
        return self::$redisClient;
    }

    /**
     * 获取HTTP连接
     *
     * @param int $timeout
     * @return Client
     */
    public static function getHttpClient($timeout = 10): Client
    {
        if (is_null(self::$httpClient)) {
            self::$httpClient = new Client([
                'timeout' => $timeout,
            ]);
        }
        return self::$httpClient;
    }

    /**
     * 获取Elasticsearch连接
     *
     * @param string[] $hosts
     * @return EsClient
     */
    public static function getEsClient($hosts = ['127.0.0.1']): EsClient
    {
        if (is_null(self::$esClient)) {
            self::$esClient = ClientBuilder::create()->setHosts($hosts)->build();
        }
        return self::$esClient;
    }

    /**
     * 存储文件
     *
     * @param string $fileName
     * @param string $data
     * @param int $flags
     */
    public static function putStorageFile(string $fileName, string $data, int $flags = 0)
    {
        $pathFile = self::getStoragePath() . '/' . trim($fileName, '/');
        file_put_contents($pathFile, $data, $flags);
    }

    /**
     * 读取文件
     *
     * @param string $fileName
     * @return string|null
     */
    public static function getStorageFile(string $fileName): ?string
    {
        $pathFile = self::getStoragePath() . '/' . trim($fileName, '/');
        if (!file_exists($pathFile)) {
            return false;
        }
        return file_get_contents($pathFile);
    }

    /**
     * 判断字符串是否以指定子串开头
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function strStartsWith(string $haystack, string $needle): bool
    {
        return 0 === strncmp($haystack, $needle, strlen($needle));
    }

    /**
     * 判断字符串是否以指定子串结尾
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function strEndsWith(string $haystack, string $needle): bool
    {
        return '' === $needle || ('' !== $haystack && 0 === substr_compare($haystack, $needle, -strlen($needle)));
    }
}