<?php

namespace Tests;

use Confusing\WangWen\Dao;
use Confusing\WangWen\XinBiQuGe;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Throwable;

class WangWenTest extends TestCase
{
    public function testRankNovel()
    {
        $wangWen = new XinBiQuGe();
        $wangWen->rankNovel();
        $this->assertTrue(true);
    }

    public function testParseNovel()
    {
        $wangWen = new XinBiQuGe();
        $list = $wangWen->parseNovel();
        Dao::saveNovel($list);
        $this->assertTrue(true);
    }

    public function testChapter()
    {
        $wangWen = new XinBiQuGe();
        $books = Dao::getNovelBatch();
        foreach ($books as $book) {
            $wangWen->chapter($book['name'], $book['uri']);
            sleep(1);
        }
        $this->assertTrue(true);
    }

    public function testParseChapter()
    {
        $wangWen = new XinBiQuGe();
        $start = 0;
        $limit = 100;

        while (true) {
            // 书籍列表
            $books = Dao::getNovelBatch($start, $limit);
            if (empty($books)) {
                break;
            }
            // 解析章节
            foreach ($books as $book) {
                try {
                    $info = $wangWen->parseChapter($book['name']);
                    Dao::updateNovelIntro($book['id'], $info['intro']);
                    Dao::updateNovelChapter(
                        $book['id'],
                        count($info['chapters'])
                    );
                    Dao::saveChapter($book['id'], $info['chapters']);
                } catch (Exception $e) {
                    echo sprintf(
                            'book: %s , error: %s',
                            $book['id'] . $book['name'],
                            $e->getMessage()
                        ) . "\n";
                }
            }
            if (count($books) < $limit) {
                break;
            }
            $start = end($books)['id'];
        }
        $this->assertTrue(true);
    }

    public function testChapterContent()
    {
        $wangWen = new XinBiQuGe();
        $start = 0;
        $limit = 20;
        while (true) {
            // 章节列表
            $chapters = Dao::getChapterBatch($start, $limit);
            if (empty($chapters)) {
                break;
            }
            // 解析章节内容
            foreach ($chapters as $ch) {
                try {
                    $text = $wangWen->chapterContent($ch['uri']);
                    Dao::saveChapterES(
                        $ch['book_id'] . '-' . $ch['seq'],
                        [
                            'title' => $ch['title'],
                            'content' => $text
                        ]
                    );
                    Dao::updateChapterSync($ch['id']);
                } catch (GuzzleException | Exception $e) {
                    echo sprintf(
                        'chapter: %s , error: %s',
                        json_encode($ch),
                        $e->getTraceAsString()
                    );
                }
            }

            if (count($chapters) < $limit) {
                break;
            }
            $start = end($chapters)['id'];
        }
        $this->assertTrue(true);
    }

    public function testChapterContentBatch()
    {
        $wangWen = new XinBiQuGe();
        $start = 0;
        $limit = 10;
        while (true) {
            // 章节列表
            $chapters = Dao::getChapterBatch($start, $limit);
            if (empty($chapters)) {
                break;
            }

            try {
                $texts = $wangWen->chapterContentBatch($chapters);
                foreach ($chapters as $ch) {
                    if (!isset($texts[$ch['id']])) {
                        continue;
                    }
                    $text = $texts[$ch['id']];
                    Dao::saveChapterES(
                        $ch['book_id'] . '-' . $ch['seq'],
                        [
                            'title' => $ch['title'],
                            'content' => $text
                        ]
                    );
                    Dao::updateChapterSync($ch['id']);
                }
            } catch (Throwable $e) {
                echo 'error: ' . $e->getTraceAsString() . "\n";
            }

            if (count($chapters) < $limit) {
                break;
            }
            $start = end($chapters)['id'];
        }
        $this->assertTrue(true);
    }

    public function testNothing()
    {
        $list = (new XinBiQuGe())->searchBookAuthor('极品');

        print_r($list);

        $this->assertTrue(true);
    }
}
