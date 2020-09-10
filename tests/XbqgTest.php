<?php


namespace Tests;

use Confusing\Util;
use Confusing\WangWen\XinBiQuGe;
use PHPUnit\Framework\TestCase;

class XbqgTest extends TestCase
{
    /**
     * 测试解析排行榜html
     */
    public function testParseHotListHtml()
    {
        $html = Util::getStorageFile('xbqg/ph_example.html');

        $info = (new XinBiQuGe())->parseHotListHtml($html);

        echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->assertTrue(true);
    }

    /**
     * 解析小说基本信息及章节目录html
     */
    public function testParseDirHtml()
    {
        $html = Util::getStorageFile('xbqg/novel_dir_example.html');

        $info = (new XinBiQuGe())->parseDirHtml($html);

        echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->assertTrue(true);
    }

    /**
     * 解析小说正文html
     */
    public function testParseChapterHtml()
    {
        $html = Util::getStorageFile('xbqg/novel_chapter_example.html');

        echo (new XinBiQuGe())->parseChapterHtml($html);

        $this->assertTrue(true);
    }

    /**
     * 解析搜索结果html
     */
    public function testParseSearchHtml()
    {
        $html = Util::getStorageFile('xbqg/search_example.html');

        $info = (new XinBiQuGe())->parseSearchHtml($html);

        echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->assertTrue(true);
    }
}
