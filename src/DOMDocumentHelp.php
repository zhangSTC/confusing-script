<?php

namespace Confusing;

use DOMDocument;
use DOMNode;
use DOMNodeList;

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
}
