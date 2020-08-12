<?php

namespace Tests;

use Confusing\Mao\Article;
use Confusing\Util;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;

class MaoTest extends TestCase
{
    /**
     * 抓取目录
     */
    public function testCatWebDir()
    {
        $mao = new Article();
        try {
            $dir = $mao->catWebDir();
            Util::putStorageFile('mao_dir.txt', '');
            foreach ($dir as $val) {
                Util::putStorageFile('mao_dir.txt', $val['url'] . '|' . $val['title'] . "\n", FILE_APPEND);
            }
            $this->assertIsArray($dir);
        } catch (GuzzleException | Exception $e) {
            echo $e->getMessage();
            echo $e->getTraceAsString();
        }
    }

    /**
     * 抓取文章内容
     */
    public function testCatWebArticleHtml()
    {
        $content = Util::getStorageFile('mao_dir.txt');
        $urls = [];
        foreach (explode("\n", $content) as $line) {
            $exp = explode('|', $line);
            if (count($exp) == 2) {
                $urls[] = [
                    'url' => $exp[0],
                    'title' => $exp[1]
                ];
            }
        }
        $this->assertFalse(empty($urls));

        $mao = new Article();
        foreach ($urls as $idx => $val) {
            $file = $idx . '.' . $val['title'] . '.html';
            if (false == Util::getStorageFile($file)) {
                $mao->catWebArticleHtml($val['url'], $file);
                usleep(500);
            }
        }
        $this->assertTrue(true);
    }

    /**
     * 解析HTML，存储文章内容至es
     */
    public function testParseArticle()
    {
        $content = Util::getStorageFile('mao_dir.txt');
        $urls = [];
        foreach (explode("\n", $content) as $line) {
            $exp = explode('|', $line);
            if (count($exp) == 2) {
                $urls[] = [
                    'url' => $exp[0],
                    'title' => $exp[1]
                ];
            }
        }
        $this->assertFalse(empty($urls));

        $esClient = Util::getEsClient();
        $mao = new Article();
        foreach ($urls as $idx => $val) {
            try {
                $file = $idx . '.' . $val['title'] . '.html';
                $data = $mao->parseArticle($file);
                if (!empty($data)) {
                    $esClient->index([
                        'index' => 'mao',
                        'type' => 'article',
                        'id' => $idx,
                        'body' => $data
                    ]);
                }
                echo 'success: ' . $file . "\n";
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
                echo 'error: ' . $file . "\n";
            }
        }
        $this->assertTrue(true);
    }
}