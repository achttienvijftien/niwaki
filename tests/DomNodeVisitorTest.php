<?php

namespace AchttienVijftien\Niwaki\Tests;

use AchttienVijftien\Niwaki\AbstractDomNodeVisitor;
use AchttienVijftien\Niwaki\DomTraverser;
use PHPUnit\Framework\TestCase;

class DomNodeVisitorTest extends TestCase
{
    private const TRAVERSE_ALL = [
        'enter a',
        'enter b',
        'enter c',
        'leave c',
        'leave b',
        'enter d',
        'enter #text',
        'leave #text',
        'leave d',
        'leave a'
    ];

    /**
     * @var \DOMDocument
     */
    protected \DOMDocument $document;

    public function setUp(): void
    {
        parent::setUp();
        $this->document = new \DOMDocument();

        $this->document->preserveWhiteSpace = false;
        $this->document->loadXML(
            '
            <root>
                <a>
                    <b>
                        <c></c>
                    </b>
                    <d>2</d>
                </a>
            </root>'
        );
    }

    public function testVisit()
    {
        $traverser = new DomTraverser($this->document->documentElement->firstChild);
        $visitor = new DomNodeVisitor();
        $traverser->addVisitor($visitor)
            ->traverse();

        $this->assertEquals(self::TRAVERSE_ALL, $visitor->events());
    }

    public function testReplaceOnEnter()
    {
        $traverser = new DomTraverser($this->document->documentElement->firstChild);
        $visitor = new DomNodeVisitor();
        $replacer = new class () extends AbstractDomNodeVisitor {
            public function enterNode(\DOMNode $node): \DOMNode|int|null
            {
                if ($node->nodeName === 'a') {
                    $replacement = $node->ownerDocument->createElement('a');
                    $c = $node->ownerDocument->createElement('c');
                    $replacement->appendChild($c);
                    return $replacement;
                }

                return null;
            }
        };
        $traverser->addVisitor($replacer)
            ->addVisitor($visitor)
            ->traverse();

        $this->assertEquals(['enter a', 'enter c', 'leave c', 'leave a'], $visitor->events());
    }

    public function testReplaceOnLeave()
    {
        $traverser = new DomTraverser($this->document->documentElement->firstChild);
        $visitor = new DomNodeVisitor();
        $replacer = new class () extends AbstractDomNodeVisitor {
            public function leaveNode(\DOMNode $node): \DOMNode|iterable|int|null
            {
                if ($node->nodeName === 'a') {
                    $replacement = $node->ownerDocument->createElement('a');
                    $c = $node->ownerDocument->createElement('c');
                    $replacement->appendChild($c);
                    return $replacement;
                }

                return null;
            }
        };
        $traverser->addVisitor($replacer)
            ->addVisitor($visitor)
            ->traverse();

        $this->assertEquals(self::TRAVERSE_ALL, $visitor->events());

        $this->assertXmlStringEqualsXmlString(
            '<root><a><c></c></a></root>',
            $this->document->saveXML()
        );
    }

    public function testReplaceWithChildNodes()
    {
        $traverser = new DomTraverser($this->document->documentElement->firstChild);
        $visitor = new DomNodeVisitor();
        $replacer = new class () extends AbstractDomNodeVisitor {
            public function leaveNode(\DOMNode $node): \DOMNode|iterable|int|null
            {
                return $node->nodeName === 'a' ? $node->childNodes : null;
            }
        };
        $traverser->addVisitor($replacer)
            ->addVisitor($visitor)
            ->traverse();

        $this->assertEquals(self::TRAVERSE_ALL, $visitor->events());
        $this->assertXmlStringEqualsXmlString(
            '<root><b><c></c></b><d>2</d></root>',
            $this->document->saveXML()
        );
    }

    public function testTraversalAfterReplaceOnEnter()
    {
        $traverser = new DomTraverser($this->document->documentElement->firstChild);
        $visitor = new DomNodeVisitor();
        $replacer = new class () extends AbstractDomNodeVisitor {
            public function enterNode(\DOMNode $node): \DOMNode|int|null
            {
                return $node->nodeName === 'a' ? $node->firstChild : null;
            }
        };
        $traverser->addVisitor($visitor)
            ->addVisitor($replacer)
            ->traverse();

        $this->assertEquals(
            [
                'enter a', // a is replaced, leave is never called
                'enter c',
                'leave c',
                'leave b', // b replaces a, enter is never called
                // d is not reached, because a was replaced
            ],
            $visitor->events()
        );
    }

    public function testReplaceMultipleOnEnterThrows()
    {
        $traverser = new DomTraverser($this->document->documentElement->firstChild);
        $replacer = new class () extends AbstractDomNodeVisitor {
            public function enterNode(\DOMNode $node): \DOMNode|int|null
            {
                return $node->childNodes;
            }
        };
        $traverser->addVisitor($replacer);

        $this->expectException(\TypeError::class);

        $traverser->traverse();
    }

    public function testRemoveNodeOnEnterThrows()
    {
        $traverser = new DomTraverser($this->document->documentElement->firstChild);
        $remover = new class () extends AbstractDomNodeVisitor {
            public function enterNode(\DOMNode $node): \DOMNode|int|null
            {
                return DomTraverser::REMOVE_NODE;
            }
        };
        $traverser->addVisitor($remover);

        $this->expectException(\BadMethodCallException::class);

        $traverser->traverse();
    }

    public function testTraversalAfterReplaceOnLeave()
    {
        $traverser = new DomTraverser($this->document->documentElement->firstChild);
        $visitor = new DomNodeVisitor();
        $replacer = new class () extends AbstractDomNodeVisitor {
            public function leaveNode(\DOMNode $node): \DOMNode|int|null
            {
                return $node->nodeName === 'a' ? $node->firstChild : null;
            }
        };
        $traverser->addVisitor($replacer)
            ->addVisitor($visitor)
            ->traverse();

        $this->assertEquals(self::TRAVERSE_ALL, $visitor->events());
    }

    public function testRemoveNode()
    {
        $traverser = new DomTraverser($this->document->documentElement->firstChild);
        $remover = new class () extends AbstractDomNodeVisitor {
            public function leaveNode(\DOMNode $node): \DOMNode|int|null
            {
                return $node->nodeName === 'a' ? DomTraverser::REMOVE_NODE : null;
            }
        };
        $traverser->addVisitor($remover)
            ->traverse();

        $this->assertXmlStringEqualsXmlString(
            '<root></root>',
            $this->document->saveXML()
        );
    }

    public function testSkipChildren()
    {
        $traverser = new DomTraverser($this->document->documentElement->firstChild);
        $visitor = new DomNodeVisitor();
        $skip = new class () extends AbstractDomNodeVisitor {
            public function enterNode(\DOMNode $node): \DOMNode|int|null
            {
                return $node->nodeName === 'a' ? DomTraverser::SKIP_CHILDREN : null;
            }
        };
        $traverser
            ->addVisitor($visitor)
            ->addVisitor($skip)
            ->traverse();

        $this->assertEquals(['enter a', 'leave a',], $visitor->events());
    }
}
