<?php

/**
 * DomNodeVisitorInterface
 * @package AchttienVijftien\Niwaki
 */

namespace AchttienVijftien\Niwaki;

interface DomNodeVisitorInterface
{
    /**
     * Called when traverser enters node $node.
     *
     * @param \DOMNode $node DOM Node.
     *
     * @throws \BadMethodCallException If visitor function returns Traversable.
     */
    public function enterNode(\DOMNode $node): \DOMNode|int|null;

    /**
     * Called when traverser leaves node $node.
     *
     * @param \DOMNode $node DOM Node.
     */
    public function leaveNode(\DOMNode $node): \DOMNode|iterable|int|null;
}
