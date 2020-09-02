<?php

namespace Tests;

use Confusing\WangWen\Dao;
use Confusing\WangWen\XinBiQuGe;
use Exception;
use PHPUnit\Framework\TestCase;

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
        $books = Dao::getNovelBatch();
        foreach ($books as $book) {
            try {
                $info = $wangWen->parseChapter($book['name']);
                Dao::updateNovelIntro($book['id'], $info['intro']);
                Dao::updateNovelChapter($book['id'], count($info['chapters']));
                Dao::saveChapter($book['id'], $info['chapters']);
            } catch (Exception $e) {
                echo sprintf(
                        'book: %s , error: %s',
                        $book['id'] . $book['name'],
                        $e->getMessage()
                    ) . "\n";
            }
        }
        $this->assertTrue(true);
    }

    public function testNothing()
    {
        $this->assertTrue(true);
    }
}
