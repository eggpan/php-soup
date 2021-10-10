<?php

declare(strict_types=1);

namespace Eggpan\PhpSoup\Element;

use DOMNode;
use Eggpan\PhpSoup\Soup;
use ErrorException;

/**
 * @property-read NavigableString|null $string
 * @property-read string $text
 * @property-read null $name
 */
class NavigableString
{
    /**
     * @param Soup    $soup
     * @param DOMNode $node
     */
    public function __construct(protected Soup $soup, protected DOMNode $node)
    {
    }

    /**
     * @return Tag
     */
    private function getParent(): Tag
    {
        /** @var DOMNode */
        $parentNode = $this->node->parentNode;
        return new Tag($this->soup, $parentNode);
    }

    /**
     * @param string $name
     * @return Tag|NavigableString|string|null
     * @throws ErrorException
     */
    public function __get(string $name): Tag|NavigableString|string|null
    {
        return match ($name) {
            'parent' => $this->getParent(),
            'string' => $this,
            'text'   => (string) $this,
            default  => throw new ErrorException("property {$name} does not exist."),
        };
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->node->textContent;
    }
}
