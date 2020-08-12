<?php

namespace Confusing\Mao;

use Confusing\Util;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

/**
 * 爬取maozedong选集
 *
 * Class Article
 * @package Confusing\Mao
 */
class Article
{
    const BASE_URI = 'https://www.marxists.org/chinese/maozedong/';
    const DIR_URI = self::BASE_URI . 'index.htm';

    /**
     * 抓取文选目录
     *
     * @return array
     * @throws Exception | GuzzleException
     */
    public function catWebDir(): array
    {
        $resp = Util::getHttpClient()->request('GET', self::DIR_URI);
        if ($resp->getStatusCode() != 200) {
            throw new Exception('http code: ' . $resp->getStatusCode());
        }
        $content = $resp->getBody()->getContents();
        $content = mb_convert_encoding($content, 'UTF-8', 'GB2312');
        $list = [];
        foreach (explode("\n", $content) as $line) {
            $match = [];
            preg_match('/<a href="(mar.*?)">(.*?)<\/a>/', $line, $match);
            if (isset($match[1]) && isset($match[2])) {
                $list[] = [
                    'url' => $match[1],
                    'title' => $match[2]
                ];
            }
        }
        return $list;
    }

    /**
     * 抓取网页源代码
     *
     * @param string $url
     * @param string $fileName
     */
    public function catWebArticleHtml(string $url, string $fileName)
    {
        try {
            $url = self::BASE_URI . $url;
            $resp = Util::getHttpClient()->request('GET', $url);
            if ($resp->getStatusCode() != 200) {
                throw new Exception('http code: ' . $resp->getStatusCode());
            }
            $content = $resp->getBody()->getContents();
            $content = mb_convert_encoding($content, 'UTF-8', 'GB2312');
            Util::putStorageFile($fileName, $content);
        } catch (GuzzleException | Exception $e) {
            echo $e->getMessage() . "\n";
            echo $e->getTraceAsString();
        }
    }

    /**
     * 解析文章内容
     *
     * @param string $fromFile
     * @return array|false
     */
    function parseArticle(string $fromFile)
    {
        $html = Util::getStorageFile($fromFile);
        if (empty($html)) {
            return false;
        }

        $txt = [];

        // 解析标题
        $matches = [];
        preg_match('/<p.*?class=\'title1\'.*?>(.*?)<\/p>/is', $html, $matches);
        $txt['title'] = $matches[1] ?? '';
        // 解析日期
        preg_match('/<p.*?class=\'date\'.*?>（(.*?)）<\/p>/is', $html, $matches);
        $txt['date'] = $matches[1] ?? '';
        // 解析说明
        preg_match('/<blockquote><font.*?color=.*?>(.*?)<\/font>/is', $html, $matches);
        $txt['tip'] = $matches[1] ?? '';
        if (empty($txt['tip'])) {
            preg_match('/<p.*?align=.*?>(.*?)<\/p>/is', $html, $matches);
            $txt['tip'] = $matches[1] ?? '';
        }
        // 解析正文
        $txt['content'] = [];
        $lines = explode("\n", $html);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!Util::strEndsWith($line, '<br>')) {
                continue;
            }
            $line = str_replace('<br>', '', $line);
            if (empty($line) || Util::strStartsWith($line, '<a') || Util::strStartsWith($line, '<hr')) {
                continue;
            }
            if (Util::strStartsWith($line, '<h3')) {
                // 子标题
                preg_match('/<h3 style=.*?>(.*?)<\/h3>/', $line, $matches);
                $subtitle = $matches[1] ?? '';
                if (!empty($subtitle)) {
                    $txt['content'][] = [
                        'type' => 'subtitle',
                        'c' => preg_replace('/<.*?>/', '', $subtitle)
                    ];
                }
            } else {
                // 段落
                $txt['content'][] = [
                    'type' => 'p',
                    'c' => preg_replace('/<.*?>/', '', $line)
                ];
            }
        }
        // 解析注释
        $txt['note'] = [];
        $exp = explode('注释', $html, 2);
        if (isset($exp[1])) {
            $lines = explode("\n", $exp[1]);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty(preg_replace('/{<.*?>}?/', '', $line))) {
                    continue;
                }
                $line = str_replace('<br>', '', $line);
                $line = str_replace('</span>', '', $line);
                if (empty($line) || (Util::strStartsWith($line, '<') && Util::strEndsWith($line, '>'))) {
                    continue;
                }
                preg_match('/<a.*?name=.*?><sup>\[(.*?)\]<\/sup><\/a>/', $line, $matches);
                $seq = $matches[1] ?? '';
                $txt['note'][$seq] = preg_replace('/<.*?>\[.*?<\/a>/', '', $line);
            }
        }
        return $txt;
    }
}
