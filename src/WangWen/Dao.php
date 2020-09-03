<?php


namespace Confusing\WangWen;

use Confusing\Util;
use PDO;

class Dao
{
    /**
     * 存储网文基本信息
     *
     * @param array $novelList
     */
    public static function saveNovel(array $novelList)
    {
        if (empty($novelList)) {
            return;
        }

        $params = [];
        foreach ($novelList as $novel) {
            $params[] = $novel['book'];
            $params[] = $novel['xclass'];
            $params[] = $novel['author'];
            $params[] = $novel['status'];
            $params[] = $novel['uri'];
        }

        $sql = 'INSERT INTO `wangwen_book` (`name`, `xclass`, `author`, `status`, `uri`) VALUES ' . trim(
                str_repeat('(?, ?, ?, ?, ?),', count($novelList)),
                ','
            );
        $stmt = Util::getPdoClient()->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * 批量查询网文信息
     *
     * @param int $start
     * @param int $limit
     * @return array
     */
    public static function getNovelBatch(int $start = 0, int $limit = 100)
    {
        $client = Util::getPdoClient();
        $stmt = $client->prepare(
            sprintf(
                'SELECT * FROM `wangwen_book` WHERE `id` > %d LIMIT %d',
                $start,
                $limit
            )
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 更新网文简介
     *
     * @param int $id
     * @param string $intro
     */
    public static function updateNovelIntro(int $id, string $intro)
    {
        if (mb_strlen($intro) > 600) {
            $intro = mb_strcut($intro, 0, 590) . '...';
        }
        $stmt = Util::getPdoClient()->prepare(
            'UPDATE `wangwen_book` SET `intro` = ? WHERE `id` = ?'
        );
        $stmt->execute([$intro, $id]);
    }

    /**
     * 更新网文章节数量
     *
     * @param int $id
     * @param int $chapter
     */
    public static function updateNovelChapter(int $id, int $chapter)
    {
        $stmt = Util::getPdoClient()->prepare(
            'UPDATE `wangwen_book` SET `chapter` = ? WHERE `id` = ?'
        );
        $stmt->execute([$chapter, $id]);
    }

    /**
     * 批量查询章节信息
     *
     * @param int $start
     * @param int $limit
     * @return array
     */
    public static function getChapterBatch(int $start = 0, int $limit = 100)
    {
        $client = Util::getPdoClient();
        $stmt = $client->prepare(
            sprintf(
                'SELECT * FROM `wangwen_chapter` WHERE `id` > %d AND `sync_at` = 0 LIMIT %d',
                $start,
                $limit
            )
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 存储章节基本信息
     *
     * @param int $id
     * @param array $chapters
     */
    public static function saveChapter(int $id, array $chapters)
    {
        if (empty($chapters)) {
            return;
        }

        $saveFunc = function ($params) {
            $sql = 'INSERT INTO `wangwen_chapter` (`book_id`, `seq`, `title`, `uri`) VALUES ' . trim(
                    str_repeat('(?, ?, ?, ?),', count($params) / 4),
                    ','
                );
            $stmt = Util::getPdoClient()->prepare($sql);
            $stmt->execute($params);
            if (intval($stmt->errorCode()) > 0) {
                echo $stmt->errorCode() . ': ' . json_encode(
                        $stmt->errorInfo()
                    ) . "\n";
            }
        };

        $params = [];
        foreach ($chapters as $idx => $ch) {
            $params[] = intval($id);
            $params[] = intval($ch['seq']);
            $params[] = trim($ch['title']);
            $params[] = trim($ch['uri']);
            if ($idx % 100 == 0 && $idx != 0) {
                $saveFunc($params);
                $params = [];
            }
        }
        if (!empty($params)) {
            $saveFunc($params);
        }
    }

    /**
     * 存储文本信息至es
     *
     * @param string $id
     * @param array $text
     * @return array|callable
     */
    public static function saveChapterES(string $id, array $text)
    {
        return Util::getEsClient()->index(
            [
                'id' => $id,
                'index' => 'wangwen',
                'type' => 'xbqg',
                'body' => $text
            ]
        );
    }

    /**
     * 更新网文同步时间
     *
     * @param int $id
     * @param int $syncAt
     */
    public static function updateChapterSync(int $id, int $syncAt = 0)
    {
        if ($syncAt <= 0) {
            $syncAt = time();
        }
        $stmt = Util::getPdoClient()->prepare(
            'UPDATE `wangwen_chapter` SET `sync_at` = ? WHERE `id` = ?'
        );
        $stmt->execute([$syncAt, $id]);
    }
}
