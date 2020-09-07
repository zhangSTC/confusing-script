<?php

namespace Confusing\WangWen;

use Confusing\DOMDocumentHelp;
use Confusing\Util;
use DOMDocument;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Throwable;

use function GuzzleHttp\Promise\unwrap;

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
     * @return string|null
     * @throws GuzzleException
     * @throws Exception
     */
    public function chapterContent(string $uri): ?string
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
        return $this->parseChapterHtml($content);
    }

    /**
     * 批量抓取章节内容
     *
     * @param array $chapters
     * @return array|null
     * @throws Throwable
     */
    public function chapterContentBatch(array $chapters): ?array
    {
        $client = Util::getHttpClient(20);
        $promises = [];
        foreach ($chapters as $ch) {
            $url = self::BASE_URI . trim($ch['uri'], '/');
            $promises[$ch['id']] = $client->requestAsync('GET', $url);
        }
        $results = unwrap($promises);

        $res = [];
        foreach ($results as $id => $resp) {
            if ($resp->getStatusCode() != 200) {
                continue;
            }
            $content = $resp->getBody()->getContents();
            $res[$id] = $this->parseChapterHtml($content);
        }
        return $res;
    }

    /**
     * 解析章节正文网页
     *
     * @param string $html
     * @return string|null
     */
    private function parseChapterHtml(string $html): ?string
    {
        if (empty($html)) {
            return '';
        }
        $match = [];

        // 使用正则表达式抓取正文部分
        preg_match('/<div.*?id="content">(.*?)<\/div>/', $html, $match);
        if (count($match) < 2) {
            return '';
        }
        $text = $match[1];

        // 替换空格及换行符
        $text = str_replace('&nbsp;', '', $text);
        $lines = [];
        foreach (explode('<br />', $text) as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $lines[] = $line;
            }
        }
        return implode("\n", $lines);
    }

    public function searchBookAuthor(string $keyword): ?array
    {
        if (empty($keyword)) {
            return null;
        }

        $url = self::BASE_URI . 'search.php';
        $resp = Util::getHttpClient(20)->request(
            'GET',
            $url,
            [
                RequestOptions::QUERY => [
                    'keyword' => $keyword
                ]
            ]
        );
        $html = $resp->getBody()->getContents();
        if (empty($html)) {
            return null;
        }

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $list = [];

        // 搜索结果列表
        $targetTag = 'div';
        $targetClass = 'result-game-item-detail';
        $nodeList = $dom->getElementsByTagName($targetTag);
        foreach (
            DOMDocumentHelp::getNodesByClass($nodeList, $targetClass) as $node
        ) {
            // 标题
            $titleNode = DOMDocumentHelp::getFirstNodeByClass(
                $node->childNodes,
                'result-game-item-title'
            );
            if (empty($titleNode)) {
                continue;
            }
            $title = str_replace(' ', '', trim($titleNode->textContent));

            // 链接
            $href = DOMDocumentHelp::getFirstNodeByName(
                $titleNode->childNodes,
                'a'
            )->attributes->getNamedItem('href')->textContent;

            // 简介
            $descNode = DOMDocumentHelp::getFirstNodeByClass(
                $node->childNodes,
                'result-game-item-desc'
            );
            if (empty($descNode)) {
                continue;
            }
            $desc = str_replace(' ', '', trim($descNode->textContent));

            // 其它信息
            $info = [];
            $infoNodes = DOMDocumentHelp::getFirstNodeByClass(
                $node->childNodes,
                'result-game-item-info'
            );
            if (empty($infoNodes)) {
                continue;
            }
            foreach (
                DOMDocumentHelp::getNodesByClass(
                    $infoNodes->childNodes,
                    'result-game-item-info-tag'
                ) as $infoTagNode
            ) {
                $infoTag = trim($infoTagNode->textContent);
                $infoTag = str_replace("\n", '', $infoTag);
                $info[] = str_replace(' ', '', $infoTag);
            }

            $list[] = [
                'title' => $title,
                'href' => $href,
                'desc' => $desc,
                'info' => $info
            ];
        }
        return $list;
    }
}
