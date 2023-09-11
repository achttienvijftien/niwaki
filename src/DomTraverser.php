<?php

/**
 * DomTraverser
 * @package AchttienVijftien\Niwaki
 */

namespace AchttienVijftien\Niwaki;

/**
 * Class DomTraverser
 */
class DomTraverser
{
    public const SKIP_CHILDREN = 1;
    public const REMOVE_NODE = 2;

    /**
     * Root DOM Node.
     *
     * @var \DOMNode
     */
    private \DOMNode $rootNode;
    /**
     * Visitors.
     *
     * @var DomNodeVisitorInterface[]
     */
    private array $visitors = [];

    /**
     * DomNodeVisitor constructor.
     *
     * @param \DOMNode $rootNode Root DOM Node.
     */
    public function __construct(\DOMNode $rootNode)
    {
        $this->rootNode = $rootNode;
    }

    /**
     * Visits all the nodes from the root node.
     */
    public function traverse(): \DOMNode
    {
        for ($node = $this->rootNode; null !== $node; $node = $nextNode) {
            $enterResult = $this->enterNode($node);

            if ($enterResult instanceof \DOMNode) {
                $node = $enterResult;
            }

            $nextNode = null;

            if (null !== $node->firstChild && self::SKIP_CHILDREN !== $enterResult) {
                // Traverse down.
                $nextNode = $node->firstChild;
            } elseif (null !== $node->nextSibling) {
                // Visit siblings.
                $nextNode = $node->nextSibling;
                $this->leaveNode($node);
            } else {
                // Traverse up until we find an ancestor with a next sibling.

                $parent = $node->parentNode; // keep reference, leaveNode() might alter it.
                $leavingRootNode = $node->isSameNode($this->rootNode);

                $this->leaveNode($node);

                if ($leavingRootNode) {
                    break;
                }

                while (null !== $parent) {
                    // Preserve references possibly mutated by leaveNode().
                    $parentNextSibling = $parent->nextSibling;
                    $parentParentNode = $parent->parentNode;
                    $leavingRootNode = $parent->isSameNode($this->rootNode);
                    $this->leaveNode($parent);

                    if ($leavingRootNode) {
                        break;
                    }

                    if (null !== $parentNextSibling) {
                        $nextNode = $parentNextSibling;
                        break;
                    }

                    $parent = $parentParentNode;
                }
            }
        }

        return $this->rootNode;
    }

    /**
     * Enter node $node, calling corresponding enter method if it exists.
     *
     * @param \DOMNode $node DOM Node.
     */
    private function enterNode(\DOMNode $node): \DOMNode|int|null
    {
        $skipChildren = false;

        foreach ($this->visitors as $visitor) {
            $visitorResult = $visitor->enterNode($node);

            if ($visitorResult instanceof \DOMNode) {
                $this->replaceNode($visitorResult, $node);
                $node = $visitorResult;
            } elseif ($visitorResult === self::REMOVE_NODE) {
                throw new \BadMethodCallException(
                    'Cannot remove a node on entering'
                );
            } elseif ($visitorResult === self::SKIP_CHILDREN) {
                $skipChildren = true;
            }
        }

        if ($skipChildren) {
            return self::SKIP_CHILDREN;
        }

        return $node;
    }

    /**
     * Leave node $node, calling corresponding leave method if it exists.
     *
     * @param \DOMNode $node DOM Node.
     */
    private function leaveNode(\DOMNode $node): void
    {
        $removeNode = false;

        foreach ($this->visitors as $visitor) {
            $visitorResult = $visitor->leaveNode($node);

            if ($visitorResult instanceof \DOMNode || $visitorResult instanceof \Traversable) {
                $this->replaceNode($visitorResult, $node);
            }

            if (self::REMOVE_NODE === $visitorResult) {
                $removeNode = true;
            }
        }

        if ($removeNode) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Replace $node with $replacement.
     * If $replacement is Traversable, inserts all nodes in place of $node.
     *
     * @param \DOMNode|\Traversable $replacement DOMNode or Traversable of DOMNodes.
     * @param \DOMNode $node Node to replace.
     */
    protected function replaceNode(\DOMNode|\Traversable $replacement, \DOMNode $node): void
    {
        if ($replacement instanceof \DOMNode && $replacement->isSameNode($node)) {
            return;
        }

        if ($replacement instanceof \Traversable) {
            $fragment = $node->ownerDocument->createDocumentFragment();
            $nodes = iterator_to_array($replacement, false);
            foreach ($nodes as $child) {
                if ($child instanceof \DOMNode) {
                    $fragment->appendChild($child);
                }
            }
            $replacement = $fragment;
        }

        $node->parentNode->replaceChild($replacement, $node);

        if ($node->isSameNode($this->rootNode)) {
            $this->rootNode = $replacement;
        }
    }

    /**
     * Add visitor to traversal.
     *
     * @param DomNodeVisitorInterface $visitor Visitor.
     * @return $this
     */
    public function addVisitor(DomNodeVisitorInterface $visitor): self
    {
        $this->visitors[] = $visitor;
        return $this;
    }
}
