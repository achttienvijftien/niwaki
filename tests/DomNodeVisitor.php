<?php

/**
 * DomNodeVisitor
 * @package AchttienVijftien\Niwaki\Tests
 */

namespace AchttienVijftien\Niwaki\Tests;

use AchttienVijftien\Niwaki\DomNodeVisitorInterface;

/**
 * Class DomNodeVisitor
 */
class DomNodeVisitor implements DomNodeVisitorInterface
{
    private array $events = [];

    public function events(): array
    {
        return $this->events;
    }

    public function enterNode(\DOMNode $node): \DOMNode|int|null
    {
        $this->events[] = "enter $node->nodeName";
        return null;
    }

    public function leaveNode(\DOMNode $node): \DOMNode|iterable|int|null
    {
        $this->events[] = "leave $node->nodeName";
        return null;
    }
}
