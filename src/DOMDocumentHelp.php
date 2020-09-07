<?php

namespace Confusing;

use DOMDocument;
use DOMNode;
use DOMNodeList;
use Generator;

class DOMDocumentHelp
{
    public static function getChildNodesCount(DOMDocument $dom): int
    {
        return $dom->childNodes->count();
    }

    public static function getFirstNode(DOMNodeList $nodeList): ?DOMNode
    {
        if (empty($nodeList) || $nodeList->count() == 0) {
            return null;
        }
        return $nodeList->item(0);
    }

    public static function getFirstNodeByName(
        DOMNodeList $nodeList,
        string $name
    ): ?DOMNode {
        for ($i = 0, $n = $nodeList->count(); $i < $n; $i++) {
            $childNode = $nodeList->item($i);
            if ($childNode->nodeName == $name) {
                return $childNode;
            }
        }
        return null;
    }

    public static function getNodesByClass(
        DOMNodeList $nodes,
        string $class
    ) {
        for ($i = 0, $n = $nodes->count(); $i < $n; $i++) {
            $node = $nodes->item($i);
            if (empty($node) || empty($node->attributes)) {
                continue;
            }

            $classCont = $node->attributes->getNamedItem(
                'class'
            );

            if (empty($classCont)) {
                continue;
            }
            if (false !== strpos($classCont->textContent, $class)) {
                yield $node;
            }
        }
    }

    public static function getFirstNodeByClass(
        DOMNodeList $nodes,
        string $class
    ): ?DOMNode {
        for ($i = 0, $n = $nodes->count(); $i < $n; $i++) {
            $node = $nodes->item($i);
            if (empty($node) || empty($node->attributes)) {
                continue;
            }

            $classCont = $node->attributes->getNamedItem(
                'class'
            );
            if (empty($classCont)) {
                continue;
            }
            if (false !== strpos($classCont->textContent, $class)) {
                return $node;
            }
        }
        return null;
    }
}
