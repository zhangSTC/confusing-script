<?php

namespace Confusing\WangWen;

use Confusing\DOMHelp;
use Confusing\Log;
use Confusing\Util;
use DOMDocument;
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

    /**
     * 抓取网页html
     *
     * @param string $url
     * @param array $params
     * @return string|null
     */
    private function spiderHtml(string $url, array $params = []): ?string
    {
        try {
            Log::info(sprintf('request url: %s, params: %s', $url, json_encode($params)));

            $client = Util::getHttpClient();
            $resp = $client->request('GET', $url, [RequestOptions::QUERY => $params]);
            if ($resp->getStatusCode() != 200) {
                Log::error('request fail! code: ' . $resp->getStatusCode());
                return null;
            }

            $content = $resp->getBody()->getContents();
            Log::info('request succcess. response: ' . substr($content, 0, 200));
            return $content;
        } catch (GuzzleException $e) {
            Log::error('spiderHtml fail! error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 搜索关键字
     *
     * @param string $keyword
     * @return array|null
     */
    public function search(string $keyword): ?array
    {
        $url = self::BASE_URI . 'search.php';
        $html = $this->spiderHtml($url, ['keyword' => $keyword]);
        if (empty($html)) {
            Log::error('spider html is empty.');
            return null;
        }
        return $this->parseSearchHtml($html);
    }

    /**
     * 获取热门排行榜
     *
     * @return array|null
     */
    public function hotList(): ?array
    {
        $url = self::BASE_URI . 'xbqgph.html';
        $html = $this->spiderHtml($url);
        if (empty($html)) {
            Log::error('spider html is empty.');
            return null;
        }
        return $this->parseHotListHtml($html);
    }

    /**
     * 获取小说基本信息及章节目录
     *
     * @param string $uri
     * @return array[]|null
     */
    public function novelDir(string $uri): ?array
    {
        $url = self::BASE_URI . trim('/', $uri);
        $html = $this->spiderHtml($url);
        if (empty($html)) {
            Log::error('spider html is empty.');
            return null;
        }
        return $this->parseDirHtml($html);
    }

    /**
     * 获取小说章节正文
     *
     * @param string $uri
     * @return string|null
     */
    public function novelChapter(string $uri): ?string
    {
        $url = self::BASE_URI . trim('/', $uri);
        $html = $this->spiderHtml($url);
        if (empty($html)) {
            Log::error('spider html is empty.');
            return null;
        }
        return $this->parseChapterHtml($html);
    }

    /**
     * 批量获取小说章节正文
     *
     * @param array $chapters
     * @return array|null
     * @throws Throwable
     */
    public function novelChapterBatch(array $chapters): ?array
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
     * 解析热门榜单html
     *
     * @param string $html
     * @return array|null
     */
    public function parseHotListHtml(string $html): ?array
    {
        $dom = new DOMDocument();
        if (!$dom->loadHTML($html)) {
            return null;
        }

        $main = $dom->getElementById('main');
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
                            $uri = $a->attributes->getNamedItem('href')->textContent;
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
     * 解析章节目录html
     *
     * @param string $html
     * @return array|null
     */
    public function parseDirHtml(string $html): ?array
    {
        $html = str_replace(['<p>', '</p>'], '', $html);
        $dom = new DOMDocument();
        if (!$dom->loadHTML($html)) {
            return null;
        }

        $info = ['chapters' => []];

        $metas = $dom->getElementsByTagName('meta');
        for ($i = 0, $n = $metas->count(); $i < $n; $i++) {
            $meta = $metas->item($i);
            if (empty($meta) || empty($meta->attributes)) {
                continue;
            }
            $property = $meta->attributes->getNamedItem('property');
            $content = $meta->attributes->getNamedItem('content');
            if (empty($property) || empty($content)) {
                continue;
            }
            switch (trim($property->textContent)) {
                // 书名
                case 'og:title'  :
                    $info['title'] = trim($content->textContent);
                    break;
                // 简介
                case 'og:description':
                    $info['intro'] = str_replace([' ', '	', "\n"], '', $content->textContent);
                    break;
                // 封面
                case 'og:image':
                    $info['cover'] = str_replace(self::BASE_URI, '', trim($content->textContent));
                    break;
                // 类型
                case 'og:novel:category':
                    $info['category'] = trim($content->textContent);
                    break;
                // 作者
                case 'og:novel:author':
                    $info['author'] = trim($content->textContent);
                    break;
                // 最后更新时间
                case 'og:novel:update_time':
                    $info['updated_at'] = trim($content->textContent);
                    break;
                // 最新章节名称
                case 'og:novel:latest_chapter_name':
                    $info['latest_chapter_name'] = trim($content->textContent);
                    break;
                // 最新章节链接
                case 'og:novel:latest_chapter_url':
                    $info['latest_chapter_url'] = str_replace(self::BASE_URI, '', trim($content->textContent));
                    break;
            }
        }

        // 章节名称
        $list = $dom->getElementById('list');
        $seq = 0;
        $dl = DOMHelp::getFirstNodeByTag($list->childNodes, 'dl');
        foreach (DOMHelp::getNodesByTag($dl->childNodes, 'dd') as $child) {
            $chapter = [];
            $chapter['seq'] = (++$seq);
            $chapter['title'] = trim($child->textContent);
            $a = DOMHelp::getFirstNodeByTag($child->childNodes, 'a');
            if (!empty($a)) {
                $chapter['uri'] = str_replace(
                    self::BASE_URI,
                    '',
                    trim($a->attributes->getNamedItem('href')->textContent)
                );
            }
            $info['chapters'][] = $chapter;
        }
        return $info;
    }

    /**
     * 解析章节正文html
     *
     * @param string $html
     * @return string|null
     */
    public function parseChapterHtml(string $html): ?string
    {
        $dom = new DOMDocument();
        if (!$dom->loadHTML($html)) {
            return null;
        }

        $content = $dom->getElementById('content');
        if (empty($content)) {
            return null;
        }

        $lines = [];
        for ($i = 0, $n = $content->childNodes->count(); $i < $n; $i++) {
            $child = $content->childNodes->item($i);
            $line = trim($child->textContent);
            if (empty($line)) {
                continue;
            }
            $lines[] = trim($line, " ");
        }
        return implode("\n", $lines);
    }

    /**
     * 解析搜索页html
     *
     * @param string $html
     * @return array|null
     */
    public function parseSearchHtml(string $html): ?array
    {
        $dom = new DOMDocument();
        if (!$dom->loadHTML($html)) {
            Log::error('load html error.');
            return null;
        }
        $list = [];

        // 搜索结果列表
        $targetTag = 'div';
        $targetClass = 'result-game-item';
        $nodeList = $dom->getElementsByTagName($targetTag);
        foreach (
            DOMHelp::getNodesByClass($nodeList, $targetClass) as $node
        ) {
            $picNode = DOMHelp::getFirstNodeByClass($node->childNodes, 'result-game-item-pic');
            $detailNode = DOMHelp::getFirstNodeByClass($node->childNodes, 'result-game-item-detail');
            if (empty($picNode) || empty($detailNode)) {
                continue;
            }
            $info = [];

            // 封面
            $a = DOMHelp::getFirstNodeByTag($picNode->childNodes, 'a');
            $img = DOMHelp::getFirstNodeByTag($a->childNodes, 'img');
            $item = $img->attributes->getNamedItem('src');
            $info['cover'] = str_replace(self::BASE_URI, '', $item->textContent);

            // 标题
            $titleNode = DOMHelp::getFirstNodeByClass($detailNode->childNodes, 'result-game-item-title');
            if (empty($titleNode) || empty($titleNode->attributes)) {
                continue;
            }
            $info['title'] = str_replace(' ', '', trim($titleNode->textContent));

            // 链接
            $a = DOMHelp::getFirstNodeByTag($titleNode->childNodes, 'a');
            if (empty($a) || empty($a->attributes)) {
                continue;
            }
            $item = $a->attributes->getNamedItem('href');
            if (empty($item)) {
                continue;
            }
            $info['href'] = str_replace(self::BASE_URI, '', $item->textContent);

            // 简介
            $descNode = DOMHelp::getFirstNodeByClass($detailNode->childNodes, 'result-game-item-desc');
            if (empty($descNode)) {
                continue;
            }
            $info['intro'] = str_replace([' ', '　', "\n"], '', trim($descNode->textContent));

            // 其它信息
            $infoNodes = DOMHelp::getFirstNodeByClass($detailNode->childNodes, 'result-game-item-info');
            if (empty($infoNodes)) {
                continue;
            }
            foreach (DOMHelp::getNodesByClass($infoNodes->childNodes, 'result-game-item-info-tag') as $infoTagNode) {
                $infoTag = str_replace(["\n", "\r", ' '], '', $infoTagNode->textContent);
                if (Util::strStartsWith($infoTag, '作者')) {
                    $info['author'] = mb_substr($infoTag, mb_strlen('作者：'));
                } elseif (Util::strStartsWith($infoTag, '类型')) {
                    $info['category'] = mb_substr($infoTag, mb_strlen('类型：'));
                } elseif (Util::strStartsWith($infoTag, '更新时间')) {
                    $info['updated_at'] = mb_substr($infoTag, mb_strlen('更新时间：'));
                } elseif (Util::strStartsWith($infoTag, '最新章节')) {
                    $info['latest_chapter_name'] = mb_substr($infoTag, mb_strlen('最新章节：'));
                    $a = DOMHelp::getFirstNodeByTag($infoTagNode->childNodes, 'a');
                    $item = $a->attributes->getNamedItem('href');
                    if (empty($item)) {
                        continue;
                    }
                    $info['latest_chapter_url'] = str_replace(self::BASE_URI, '', $item->textContent);
                }
            }

            $list[] = $info;
        }
        return $list;
    }
}
