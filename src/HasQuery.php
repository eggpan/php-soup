<?php

declare(strict_types=1);

namespace Eggpan\PhpSoup;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMText;
use DOMXPath;
use Eggpan\PhpSoup\Element\Comment;
use Eggpan\PhpSoup\Element\NavigableString;
use Eggpan\PhpSoup\Element\ResultSet;
use Eggpan\PhpSoup\Element\Tag;
use Eggpan\PhpSoup\Soup;
use ErrorException;

abstract class HasQuery
{
    /** @var Soup document Soup object */
    protected Soup $soup;
    /** @var DOMDocument document object */
    protected DOMDocument $document;
    /** @var DOMNode current dom node */
    protected DOMNode $node;

    // スペース区切りを配列として扱う属性。
    // from bs4.builder import builder_registry
    // builder_registry.lookup('html').DEFAULT_CDATA_LIST_ATTRIBUTES
    private const LIST_ATTRIBUTES = [
        'accept-charset',
        'accesskey',
        'archive',
        'class',
        'dropzone',
        'for',
        'headers',
        'rel',
        'rev',
        'sandbox',
        'sizes',
    ];

    /**
     * Tag’s descendants and retrieves all descendants that match your filters, return one result.
     *
     * @param array|bool|string|null     $name
     * @param array|string               $attrs     search tag attributes
     * @param bool                       $recursive If you want to have direct children only considered, pass false
     * @param array|bool|string|null     $text      search textContent
     * @param array|bool|int|string|null ...$kwargs keyword arguments
     * @return Tag|NavigableString|null
     * @throws ErrorException When node information cannot be obtained correctly
     */
    public function find(
        array|bool|string $name = null,
        array|string $attrs = [],
        bool $recursive = true,
        array|string|bool $text = null,
        array|bool|int|string|null ...$kwargs,
    ): Tag|NavigableString|null {
        $query = $this->makeQuery($name, $attrs, $recursive, $text, 1, ...$kwargs);
        $nodeList = $this->query($query);
        if ($nodeList->count() === 0) {
            return null;
        }

        /** @var DomNode */
        $node = $nodeList->item(0);

        $soup = $this->getSoup();
        $newNode = match (true) {
            $node instanceof DOMElement => new Tag($soup, $node),
            $node instanceof DOMText    => new NavigableString($soup, $node),
            $node instanceof DOMComment => new Comment($soup, $node),
            default                     => null,
        };

        if (is_null($newNode)) {
            throw new ErrorException(
                'Unknown node type: ' . get_class($node)
            );
        }

        return $newNode;
    }

    /**
     * Tag’s descendants and retrieves all descendants that match your filters.
     *
     * @param array|bool|string|null $name
     * @param array|string           $attrs
     * @param bool                   $recursive
     * @param array|string|bool|null $text
     * @param int                    $limit
     * @param array|bool|int|string  ...$kwargs
     * @return ResultSet
     */
    public function findAll(
        array|string|bool $name = null,
        array|string $attrs = [],
        bool $recursive = true,
        array|string|bool $text = null,
        int $limit = 0,
        array|bool|int|string|null ...$kwargs,
    ): ResultSet {
        $query = $this->makeQuery($name, $attrs, $recursive, $text, $limit, ...$kwargs);

        return  $this->xPath($query);
    }

    /**
     * Alias for findAll()
     *
     * @param array|bool|string|null $name
     * @param array|string           $attrs
     * @param bool                   $recursive
     * @param array|string|bool|null $text
     * @param int                    $limit
     * @param array|bool|int|string  ...$kwargs
     * @return ResultSet
     */
    public function find_all(
        array|string|bool $name = null,
        array|string $attrs = [],
        bool $recursive = true,
        array|string|bool $text = null,
        int $limit = 0,
        array|bool|int|string|null ...$kwargs,
    ): ResultSet {
        return $this->findAll($name, $attrs, $recursive, $text, $limit, ...$kwargs);
    }

    /*
        To Be Implemented
        public function selectOne(string $selector): Tag|NavigableString|null
        {
        }
        public function select_one(string $selector): Tag|NavigableString|null
        {
            return $this->selectOne($selector);
        }
        public function select(string $selector): ResultSet
        {
        }
    */

    /**
     * Search for an element in xpath and get all descendants that match the filter.
     *
     * @param string $query
     * @return ResultSet
     */
    public function xPath(string $query): ResultSet
    {
        $nodeList = $this->query($query);

        return new ResultSet($this->getSoup(), $nodeList);
    }

    /**
     * @param string $query XPath query string.
     * @return DOMNodeList
     * @throws ErrorException If you can't get DOMNodeList
     */
    protected function query(string $query): DOMNodeList
    {
        /** @var DOMDocument */
        $document = $this instanceof Soup ? $this->document : $this->node->ownerDocument;
        $xPath = new DOMXPath($document);
        $node = $this instanceof Soup ? null : $this->node;
        // echo "debug do query '$query' node:{$node?->nodeName}" . PHP_EOL;
        $nodeList = $xPath->evaluate($query, $node);

        if (! $nodeList instanceof DOMNodeList) {
            throw new ErrorException("invalid query: $query");
        }

        return $nodeList;
    }

    /**
     * Checks whether a given array is a list
     *
     * @param array $array
     * @return bool
     */
    private function arrayIsList(array $array): bool
    {
        return array_key_first($array) === 0 && array_key_last($array) === (count($array) - 1);
    }

    /**
     * @return Soup
     */
    private function getSoup(): Soup
    {
        return $this instanceof Soup ? $this : $this->soup;
    }

    /**
     * Generate XPath query string
     *
     * @param array|bool|string|null $name      html tags to search
     * @param array|string           $attrs     attribute to search
     * @param bool                   $recursive whether to use recursive search, and whether to prepend '//' in XPath
     * @param array|bool|string|null $text      textContent to search
     * @param int                    $limit     maximum number of elements detected
     * @param array|bool|int|string  ...$kwargs keyword arguments. Overrides the definition of $attrs
     * @return string
     * @throws ErrorException If invalid values
     */
    private function makeQuery(
        array|bool|string $name = null,
        array|string $attrs,
        bool $recursive,
        array|string|bool $text = null,
        int $limit = 0,
        array|bool|int|string|null ...$kwargs,
    ): string {

        // If $attrs contains a string, search for it regardless of attributes
        // This will be converted to '//span[@*="attr-value"]'
        if (is_string($attrs)) {
            $attrs = ['*' => $attrs];
        }

        // If $kwargs contains a string, search for 'kwargs' attributes
        // @phpstan-ignore-next-line
        if (is_string($kwargs)) {
            $kwargs = ['kwargs' => (string) $kwargs];
        }

        $name = $this->organizeNameVariable($name, $text);

        // arrayはspan[text()="xxx"] | span[text()="yyy"] のようにして複数検索
        // trueはspan[text()] のようにしてテキストありのものを検索
        // nullはクエリに何も追加せず、テキスト有無に関わらず全てを検索
        $text = $this->organizeTextVariable($text);

        if ($this instanceof Soup) {
            $prefix = $recursive ? '//' : '';
        } else {
            $prefix = $recursive ? './/' : '';
        }

        $attrs = array_merge($attrs, $kwargs);

        // Search for text only. This is for textContent, no elements will be selected.
        if (is_null($name) && count($attrs) === 0) {
            if ($text === true) {
                return $prefix . 'text()';
            } elseif (is_array($text)) {
                $baseQuery = implode(
                    ' | ',
                    array_map(
                        fn($t) => $prefix . 'text()[.="' . $t . '"] | ' . $prefix . 'text()[.="' . $t . '"]',
                        $text
                    )
                );
                return $limit > 0
                    ? '(' . $baseQuery . ")[position() <= {$limit}]"
                    : $baseQuery;
            }
        }

        if (is_null($name)) {
            $name = ['*'];
        }
        return $this->makeQueryForTagSearch($name, $attrs, $text, $prefix, $limit);
    }

    /**
     * Generate XPath query string for elements
     *
     * @param array           $name
     * @param array           $attrs
     * @param array|true|null $text
     * @param string          $prefix
     * @param int             $limit
     * @return string
     */
    private function makeQueryForTagSearch(
        array $name,
        array $attrs,
        array|bool|null $text,
        string $prefix,
        int $limit,
    ): string {
        $queries = [];

        foreach ($name as $n) {
            $attributesQuery = '';
            if (count($attrs) > 0 && ! $this->arrayIsList($attrs)) {
                foreach ($attrs as $attrName => $attrValues) {
                    $attrQueries = [];
                    if (is_null($attrValues)) {
                        $attrQueries[] = "not(@{$attrName})";
                    } else {
                        foreach ((array) $attrValues as $attrValue) {
                            if ($attrValue === true) {
                                $attrQueries[] = "@{$attrName}";
                            } elseif (in_array($attrName, self::LIST_ATTRIBUTES, true)) {
                                $attrQueries[] = "contains(@{$attrName}, \"{$attrValue}\")";
                            } else {
                                $attrQueries[] = "@{$attrName}=\"{$attrValue}\"";
                            }
                        }
                    }

                    $attributesQuery .= '[' . implode(' or ', $attrQueries) . ']';
                }
            }

            // multi valued attribute
            if ($this->arrayIsList($attrs)) {
                foreach ($attrs as $attrValue) {
                    if ($text === true) {
                        $queries[] = $prefix . $n . '[@*="' . $attrValue . '"][comment()] | '
                            . $prefix . $n . '[@*="' . $attrValue . '"][text()]';
                    } elseif (is_null($text)) {
                        $queries[] = "{$prefix}{$n}[@*=\"{$attrValue}\"]";
                    } else {
                        foreach ($text as $t) {
                            $queries[] = $prefix . $n . '[@*="' . $attrValue . '"][comment()="' . $t . '"] | '
                                . $prefix . $n . '[@*="' . $attrValue . '"][text()="' . $t . '"]';
                        }
                    }
                }
            } else {
                if ($text === true) {
                    $queries[] = $prefix . $n . $attributesQuery . '[comment()] | ' . $prefix . $n . '[text()]';
                } elseif (is_null($text)) {
                    $queries[] = $prefix . $n . $attributesQuery;
                } else {
                    foreach ($text as $t) {
                        $queries[] = $prefix . $n . $attributesQuery . '[.//comment()="' . $t . '"] | '
                            . $prefix . $n . $attributesQuery . '[.//text()="' . $t . '"]';
                    }
                }
            }
        }

        return $limit > 0
            ? '(' . implode(' | ', $queries) . ")[position() <= {$limit}]"
            : implode(' | ', $queries);
    }

    /**
     * Organize $name, make sure it is array|null
     *
     * @param array|string|bool|null $name
     * @param array|string|bool|null $text
     * @return array|null
     */
    private function organizeNameVariable(array|string|bool $name = null, array|string|bool $text = null): array|null
    {
        // If $name === null, there are no attrs, so search with text()
        if ($name === false) {
            $name = null;
        } elseif ($name === true) {
            $name = '*';
        }

        // If $name is null and $text is false or '', set * to search all tags.
        if (is_null($name) && empty($text)) {
            $name = '*';
        }

        if (is_string($name)) {
            $name = (array) $name;
        }
        return $name;
    }

    /**
     * Organize $text, make sure it is array|true
     *
     * @param array|string|bool|null $text
     * @return array|true|null
     */
    private function organizeTextVariable(array|string|bool $text = null): array|bool|null
    {
        if ($text === false || is_null($text)) {
            return null;
        }

        if (is_string($text)) {
            $text = (array) $text;
        }
        return $text;
    }
}
