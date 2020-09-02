<?php

namespace Confusing\WangWen;

use Confusing\DOMDocumentHelp;
use Confusing\Util;
use DOMDocument;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

/**
 * 新笔趣阁
 *
 * Class XinBiQuGe
 * @package Confusing\WangWen
 */
class XinBiQuGe
{
    const BASE_URI = 'https://www.xsbiquge.com/';
    const RANK_URI = self::BASE_URI . 'xbqgph.html';

    /**
     * 抓取排行榜目录
     *
     * @throws Exception | GuzzleException
     */
    public function rankNovel()
    {
        $resp = Util::getHttpClient()->request('GET', self::RANK_URI);
        if ($resp->getStatusCode() != 200) {
            throw new Exception('http code: ' . $resp->getStatusCode());
        }
        $content = $resp->getBody()->getContents();
        Util::putStorageFile('wangwenrank.html', $content);
    }

    /**
     * 解析目录页的html
     *
     * @return array
     */
    public function parseNovel(): array
    {
        $doc = new DOMDocument();
        $doc->loadHTML(Util::getStorageFile('wangwenrank.html'));
        $main = $doc->getElementById('main');
        $ul = $main->getElementsByTagName('ul')->item(0);

        $list = [];
        for ($i = 0, $n = $ul->childNodes->count(); $i < $n; $i++) {
            $li = $ul->childNodes->item($i);
            if (empty(trim($li->textContent))) {
                continue;
            }
            $item = [];
            for ($j = 0, $n1 = $li->childNodes->count(); $j < $n1; $j++) {
                $span = $li->childNodes->item($j);
                if ($span->nodeName != 'span') {
                    continue;
                }
                switch ($span->attributes->getNamedItem('class')->textContent) {
                    case 's1':
                        $item['xclass'] = trim($span->textContent);
                        $item['xclass'] = trim($item['xclass'], '[]');
                        break;
                    case 's2':
                        $item['book'] = trim($span->textContent);
                        $a = $span->childNodes->item(0);
                        if ($a->nodeName == 'a') {
                            $uri = $a->attributes->getNamedItem(
                                'href'
                            )->textContent;
                            $item['uri'] = trim($uri);
                        }
                        break;
                    case 's3':
                        $item['latest'] = trim($span->textContent);
                        break;
                    case 's4':
                        $item['author'] = trim($span->textContent);
                        break;
                    case 's5':
                        $item['updated_at'] = trim($span->textContent);
                        break;
                    case 's6':
                        if (trim($span->textContent) == '连载中') {
                            $item['status'] = 1;
                        } else {
                            $item['status'] = 2;
                        }
                        break;
                }
            }
            if (!isset($item['uri'])) {
                continue;
            }
            $list[] = $item;
        }
        return $list;
    }

    /**
     * 抓取章节目录
     *
     * @param string $name
     * @param string $uri
     * @throws Exception | GuzzleException
     */
    public function chapter(string $name, string $uri)
    {
        $url = self::BASE_URI . trim($uri, '/');
        $resp = Util::getHttpClient(20)->request('GET', $url);
        if ($resp->getStatusCode() != 200) {
            throw new Exception('http code: ' . $resp->getStatusCode());
        }
        $content = $resp->getBody()->getContents();
        Util::putStorageFile($name . '.html', $content);
    }

    /**
     * 解析章节html
     *
     * @param string $fileName
     * @return array|null
     */
    public function parseChapter(string $fileName): ?array
    {
        $info = [];

        $html = Util::getStorageFile($fileName . '.html');
        if ($html == false) {
            return null;
        }

        $doc = new DOMDocument();
        $doc->loadHTML($html);

        // 简介
        $intro = $doc->getElementById('intro');
        $p = DOMDocumentHelp::getFirstNodeByName($intro->childNodes, 'p');
        if (!empty($p)) {
            $info['intro'] = $p->textContent;
        }

        // 章节名称
        $list = $doc->getElementById('list');
        $info['chapters'] = [];
        $seq = 0;
        $dl = DOMDocumentHelp::getFirstNodeByName($list->childNodes, 'dl');
        for ($i = 0, $n = $dl->childNodes->count(); $i < $n; $i++) {
            $c = $dl->childNodes->item($i);
            if ($c->nodeName == 'dd') {
                $chapter['seq'] = (++$seq);
                $chapter['title'] = $c->textContent;
                $a = DOMDocumentHelp::getFirstNodeByName($c->childNodes, 'a');
                if (!empty($a)) {
                    $chapter['uri'] = $a->attributes->getNamedItem(
                        'href'
                    )->textContent;
                }
                $info['chapters'][] = $chapter;
            }
        }
        return $info;
    }

    /**
     * 抓取章节内容
     *
     * @param string $uri
     * @return array|null
     * @throws Exception | GuzzleException
     */
    public function chapterContent(string $uri): ?array
    {
        $url = self::BASE_URI . trim($uri, '/');
        $resp = Util::getHttpClient(20)->request('GET', $url);
        if ($resp->getStatusCode() != 200) {
            throw new Exception('http code: ' . $resp->getStatusCode());
        }
        $content = $resp->getBody()->getContents();
        if (empty($content)) {
            return null;
        }

        $match = [];
        preg_match('/<div.*?id="content">(.*?)<\/div>/', $content, $match);
        if (count($match) < 2) {
            return null;
        }
        $text = $match[1];
        $text = str_replace('&nbsp;', '', $text);
        $lines = [];
        foreach (explode('<br />', $text) as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $lines[] = $line;
            }
        }
        return $lines;
    }
}
