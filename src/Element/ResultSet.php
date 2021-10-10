<?php

declare(strict_types=1);

namespace Eggpan\PhpSoup\Element;

use ArrayAccess;
use ArrayIterator;
use Countable;
use DOMNodeList;
use Eggpan\PhpSoup\Soup;
use ErrorException;
use IteratorAggregate;

/**
 * @property-read array $elements array of class Eggpan\PhpSoup\Element\NavigableString
 *                                or class Eggpan\PhpSoup\Element\Tag
 * @property-read int   $length   count of nodes
 */
class ResultSet implements ArrayAccess, Countable, IteratorAggregate
{
    /** @var array NodeList elements */
    private array $elements = [];

    /**
     * @param Soup        $soup
     * @param DOMNodeList $nodeList
     * @throws ErrorException
     */
    public function __construct(private Soup $soup, private DOMNodeList $nodeList)
    {
        foreach ($nodeList as $node) {
            $class = get_class($node);
            $this->elements[] = match ($class) {
                'DOMElement' => new Tag($this->soup, $node),
                'DOMText'    => new NavigableString($this->soup, $node),
                'DOMComment' => new Comment($this->soup, $node),
                default      => throw new ErrorException("Unknown class: $class"),
            };
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_map(fn($el) => (string) $el, $this->elements);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->nodeList->length;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->elements);
    }

    /**
     * @param string $name
     * @return array|int
     * @throws ErrorException
     */
    public function __get(string $name): array|int
    {
        return match ($name) {
            'elements' => $this->elements,
            'length' => $this->count(),
            default => throw new ErrorException("property {$name} does not exist."),
        };
    }

    /**
     * Undocumented function
     *
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->elements);
    }

    /**
     * Undocumented function
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->elements[$offset] ?? null;
    }

    /**
     * Undocumented function
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->elements[$offset] = $value;
    }

    /**
     * Undocumented function
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->elements[$offset]);
    }
}
