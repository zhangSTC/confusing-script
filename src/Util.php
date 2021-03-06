<?php

namespace Confusing;

use Elasticsearch\Client as EsClient;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PDO;
use Redis;

/**
 * 工具
 *
 * Class Util
 * @package Confusing
 */
class Util
{
    private static $redisClient;

    private static $httpClient;

    private static $esClient;

    private static $pdoClient;

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
     * 获取PDO连接
     *
     * @param string $host
     * @param string $db
     * @param int $port
     * @param string $user
     * @param string $password
     * @return PDO
     */
    public static function getPdoClient(
        string $host = '127.0.0.1',
        string $db = 'confusing',
        int $port = 3306,
        string $user = 'root',
        string $password = '123456'
    ): PDO {
        if (is_null(self::$pdoClient)) {
            $dsn = sprintf(
                'mysql:dbname=%s;host=%s;port=%d;charset=utf8mb4',
                $db,
                $host,
                $port
            );
            self::$pdoClient = new PDO($dsn, $user, $password);
        }
        return self::$pdoClient;
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
    public static function getHttpClient(int $timeout = 10): Client
    {
        if (is_null(self::$httpClient)) {
            self::$httpClient = new Client(
                [
                    RequestOptions::TIMEOUT => $timeout
                ]
            );
        }
        return self::$httpClient;
    }

    /**
     * 获取Elasticsearch连接
     *
     * @param string[] $hosts
     * @return EsClient
     */
    public static function getEsClient(array $hosts = ['127.0.0.1']): EsClient
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
     * @return bool
     */
    public static function putStorageFile(string $fileName, string $data, int $flags = 0)
    {
        $pathFile = self::getStoragePath() . '/' . trim($fileName, '/');
        $ret = file_put_contents($pathFile, $data, $flags);
        return $ret !== false;
    }

    /**
     * 创建文件夹
     *
     * @param string $dir
     * @return bool
     */
    public static function mkStorageDir(string $dir)
    {
        $dirPath = self::getStoragePath() . '/' . trim($dir, '/');
        if (is_dir($dirPath)) {
            return true;
        }
        return mkdir($dirPath);
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
        return '' === $needle ||
            ('' !== $haystack && 0 === substr_compare(
                    $haystack,
                    $needle,
                    -strlen($needle)
                ));
    }
}
