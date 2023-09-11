<?php

/**
 * AbstractDomNodeVisitor
 *
 * @package AchttienVijftien\Niwaki;
 */

namespace AchttienVijftien\Niwaki;

/**
 * Class AbstractDomNodeVisitor
 */
class AbstractDomNodeVisitor implements DomNodeVisitorInterface
{
    public function enterNode(\DOMNode $node): \DOMNode|int|null
    {
        return null;
    }

    public function leaveNode(\DOMNode $node): \DOMNode|iterable|int|null
    {
        return null;
    }
}
