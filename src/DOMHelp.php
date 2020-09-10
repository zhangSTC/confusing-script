<?php

namespace Confusing;

use DOMNode;
use DOMNodeList;
use Generator;

/**
 * DOMDocument辅助函数
 *
 * Class DOMHelp
 * @package Confusing
 */
class DOMHelp
{
    /**
     * 根据标签名获取子节点
     *
     * @param DOMNodeList $nodeList
     * @param string $name
     * @return DOMNode|null
     */
    public static function getFirstNodeByTag(DOMNodeList $nodeList, string $name): ?DOMNode
    {
        for ($i = 0, $n = $nodeList->count(); $i < $n; $i++) {
            $child = $nodeList->item($i);
            if (empty($child)) {
                continue;
            }
            if ($child->nodeName == $name) {
                return $child;
            }
        }
        return null;
    }

    /**
     * 遍历同标签子节点
     *
     * @param DOMNodeList $nodeList
     * @param string $name
     * @return Generator
     */
    public static function getNodesByTag(DOMNodeList $nodeList, string $name): Generator
    {
        for ($i = 0, $n = $nodeList->count(); $i < $n; $i++) {
            $child = $nodeList->item($i);
            if (empty($child)) {
                continue;
            }
            if ($child->nodeName == $name) {
                yield $child;
            }
        }
    }

    /**
     * 根据类名获取子节点
     *
     * @param DOMNodeList $nodes
     * @param string $class
     * @return DOMNode|null
     */
    public static function getFirstNodeByClass(DOMNodeList $nodes, string $class): ?DOMNode
    {
        for ($i = 0, $n = $nodes->count(); $i < $n; $i++) {
            $child = $nodes->item($i);
            if (empty($child) || empty($child->attributes)) {
                continue;
            }

            $classItem = $child->attributes->getNamedItem('class');
            if (empty($classItem)) {
                continue;
            }
            foreach (explode(' ', $classItem->textContent) as $c) {
                if ($class == trim($c)) {
                    return $child;
                }
            }
        }
        return null;
    }

    /**
     * 遍历同类名子节点
     *
     * @param DOMNodeList $nodes
     * @param string $class
     * @return Generator
     */
    public static function getNodesByClass(DOMNodeList $nodes, string $class): Generator
    {
        for ($i = 0, $n = $nodes->count(); $i < $n; $i++) {
            $child = $nodes->item($i);
            if (empty($child) || empty($child->attributes)) {
                continue;
            }

            $classItem = $child->attributes->getNamedItem('class');
            if (empty($classItem)) {
                continue;
            }
            foreach (explode(' ', $classItem->textContent) as $c) {
                if ($class == trim($c)) {
                    yield $child;
                }
            }
        }
    }
}
