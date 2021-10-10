<?php

declare(strict_types=1);

namespace Eggpan\PhpSoup\Tests;

use Eggpan\PhpSoup\Soup;
use PHPUnit\Framework\TestCase;

class SoupTest extends TestCase
{
    /** @var Soup */
    private Soup $byNameTree;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->byNameTree = new Soup('<a>First tag.</a>
            <b>Second tag.</b>
            <div>Third <a>Nested tag.</a> tag.</div>');
    }

    /**
     * Undocumented function
     *
     * @param mixed $expected
     * @param mixed $actual
     * @return void
     */
    private function assertSelects(mixed $expected, mixed $actual): void
    {
        $selectedNodes = [];
        foreach ($actual as $node) {
            $selectedNodes[] = $node->string;
        }
        $this->assertEquals($expected, $selectedNodes);
    }

    /**
     * Constructor and String Interpolation
     *
     * @return void
     */
    public function testConstructorAndStringInterpolation(): void
    {
        $soup = new Soup("<!DOCTYPE html>\n<p>Foo</p>");
        $this->assertEquals("<!DOCTYPE html>\n<html><body><p>Foo</p></body></html>\n", $soup);
    }

    /**
     * find() by $name
     *
     * @return void
     */
    public function testFindTag(): void
    {
        $soup = new Soup('<a>1</a><b>2</b><a>3</a><b>4</b>');
        $this->assertEquals('2', $soup->find('b')?->string);
    }

    /**
     * find() by unicode textContent
     *
     * @return void
     */
    public function testFindUnicodeText(): void
    {
        $soup = new Soup('<h1>寿司🍣</h1>');
        $this->assertEquals('寿司🍣', $soup->find(text: '寿司🍣'));
    }

    /**
     * find() by unicode attribute
     *
     * @return void
     */
    public function testFindUnicodeAttribute(): void
    {
        $soup = new Soup('<h1 id="寿司🍣">here it is</h1>');
        $this->assertEquals('here it is', $soup->find(id: '寿司🍣')?->text);
    }

    /**
     * findAll() without param
     *
     * @return void
     */
    public function testFindAllEverything(): void
    {
        $soup = new Soup('<a>foo</a><b>bar</b>');
        $this->assertEquals(4, $soup->findAll()->length);
    }

    /**
     * findAll() by $name
     *
     * @return void
     */
    public function testFindAllEverythingByName(): void
    {
        $soup = new Soup('<a>foo</a><b>bar</b><a>baz</a>');
        $this->assertEquals(2, count($soup->findAll('a')));
    }

    /**
     * findAll() by textContent
     *
     * @return void
     */
    public function testFindAllTextNodes(): void
    {
        $soup = new Soup('<html>Foo<b>bar</b>baz</html>');

        $this->assertSelects(['bar'], $soup->findAll(text: 'bar'));
        $this->assertSelects(['Foo', 'bar'], $soup->findAll(text: ['Foo', 'bar']));
        $this->assertSelects(['Foo', 'bar', 'baz'], $soup->findAll(text: true));
    }

    /**
     * findAll() with limit
     *
     * @return void
     */
    public function testFindAllLimit(): void
    {
        $soup = new Soup('<a>1</a><a>2</a><a>3</a><a>4</a><a>5</a>');

        $this->assertSelects(['1', '2', '3'], $soup->findAll('a', limit: 3));
        $this->assertSelects(['1'], $soup->findAll('a', limit: 1));
        $this->assertSelects(['1', '2', '3', '4', '5'], $soup->findAll('a', limit: 10));
        $this->assertSelects(['1', '2', '3', '4', '5'], $soup->findAll('a', limit: 0));
    }

    /**
     * findAll() return type is ResultSet and always has source property.
     * Unimplemented source property
     *
     * @return void
     */
    // public function testFindAllResultset(): void
    // {
    //     $soup = new Soup('<a></a>');
    //     $result = $soup->findAll('a');
    //     $this->assertTrue(property_exists($result, 'source'));
    //     $result = $soup->findAll(true);
    //     $this->assertTrue(property_exists($result, 'source'));
    //     $result = $soup->findAll(text: 'foo');
    //     $this->assertTrue(property_exists($result, 'source'));
    // }

    /**
     * findAll() with namespace
     * Unimplemented namespace support
     *
     * @return void
     */
    // public function testFindByNamespacedName(): void
    // {
    //     $soup = new Soup('<mathml:msqrt>4</mathml:msqrt><a svg:fill="red">');
    //     $this->assertEquals('4', $soup->find('mathml:msqrt')?->string);
    //     $this->assertEquals('a', $soup->find(attrs: ['svg:fill' => 'red'])?->name);
    // }

    /**
     * findAll() by $name
     *
     * @return void
     */
    public function testFindAllByTagName(): void
    {
        $this->assertSelects(['First tag.', 'Nested tag.'], $this->byNameTree->findAll('a'));
    }

    /**
     * findAll() by $name and textContent
     *
     * @return void
     */
    public function testFindAllByNameAndText(): void
    {
        $this->assertSelects(
            ['First tag.'],
            $this->byNameTree->findAll('a', text: 'First tag.'),
        );

        $this->assertSelects(
            ['First tag.', 'Nested tag.'],
            $this->byNameTree->findAll('a', text: true),
        );
    }

    /**
     * findAll() on non root (on Tag class).
     *
     * @return void
     */
    public function testFindAllOnNonRootElement(): void
    {
        $this->assertSelects(['Nested tag.'], $this->byNameTree->div?->findAll('a'));
    }

    /**
     * Unimplemented SoupStrainer class
     *
     * @return void
     */
    // public function testFindAllByTagStrainer(): void
    // {
    //     $this->assertSelects(
    //         ['First tag.', 'Nested tag.'],
    //         $this->byNameTree->findAll(SoupStrainer('a')),
    //     );
    // }

    /**
     * findAll() by $name array
     *
     * @return void
     */
    public function testFindAllByTagNames(): void
    {
        $this->assertSelects(['First tag.', 'Second tag.', 'Nested tag.'], $this->byNameTree->findAll(['a', 'b']));
    }

    /**
     * findAll() by multi valued attributes
     *
     * @return void
     */
    public function testFindWithMultiValuedAttribute(): void
    {
        $soup = new Soup("<div class='a b'>1</div><div class='a c'>2</div><div class='a d'>3</div>");
        $r1 = $soup->find('div', 'a d');
        $this->assertEquals('3', (string) $r1?->string);

        $r2 = $soup->findAll('div', ['a b', 'a d']);
        $this->assertSelects(['1', '3'], $r2);
    }

    /**
     * findAll() by attribute value
     *
     * @return void
     */
    public function testFindAllByAttributeName(): void
    {
        $tree = new Soup('
            <a id="first">Matching a.</a>
            <a id="second">
              Non-matching <b id="first">Matching b.</b>a.
            </a>');
        $this->assertSelects(["Matching a.", "Matching b."], $tree->findAll(id: 'first'));
    }

    /**
     * findAll() by unicode attribute value
     *
     * @return void
     */
    public function testFindAllByUtf8AttributeValue(): void
    {
        $peace = '☮';
        $soup = new Soup('<a title="☮"></a>');

        $this->assertEquals([$soup->a], $soup->findAll(title: $peace)->elements);
        $this->assertEquals([$soup->a], $soup->findAll(title: [$peace, 'something else'])->toArray());
    }

    /**
     * findAll() by attributes dictionary
     *
     * @return void
     */
    public function testFindAllByAttributeDict(): void
    {
        $tree = new Soup('
            <a name="name1" class="class1">Name match.</a>
            <a name="name2" class="class2">Class match.</a>
            <a name="name3" class="class3">Non-match.</a>
            <name1>A tag called \'name1\'.</name1>');

        $this->assertSelects(["A tag called 'name1'."], $tree->findAll(name: 'name1'));

        $this->assertSelects(['Name match.'], $tree->findAll(attrs: ['name' => 'name1']));

        $this->assertSelects(['Class match.'], $tree->findAll(attrs: ['class' => 'class2']));
    }

    /**
     * findAll() by class attribute
     *
     * @return void
     */
    public function testFindAllByClass(): void
    {
        $tree = new Soup('
            <a class="1">Class 1.</a>
            <a class="2">Class 2.</a>
            <b class="1">Class 1.</b>
            <c class="3 4">Class 3 and 4.</c>');

        $this->assertSelects(['Class 1.'], $tree->findAll('a', class: '1'));
        $this->assertSelects(['Class 3 and 4.'], $tree->findAll('c', class: '3'));
        $this->assertSelects(['Class 3 and 4.'], $tree->findAll('c', class: '4'));

        $this->assertSelects(['Class 1.'], $tree->findAll('a', '1'));
        $this->assertSelects(['Class 1.', 'Class 1.'], $tree->findAll(attrs: '1'));

        // TODO classなのでcontainsでマッチできるようにする必要がありそう。現状だと '3 4' じゃないとマッチングできない。
        // @*="xx" or contains(@*, "xx") でよさげか
        // $this->assertSelects(['Class 3 and 4.'], $tree->findAll('c', '3'));
        // $this->assertSelects(['Class 3 and 4.'], $tree->findAll('c', '4'));
    }

    /**
     * findAll() by class and multiple classes in html.
     * Unimplemented regexp search
     *
     * @return void
     */
    // public function testFindByClassWhenMultipleClassesPresent(): void
    // {
    //     $tree = new Soup("<gar class='foo bar'>Found it</gar>");

    //     $f = $tree->findAll("gar", class: re.compile("o"));
    //     $this->assertSelects($f, ["Found it"]);

    //     $f = $tree->findAll("gar", class: re.compile("a"));
    //     $this->assertSelects($f, ["Found it"]);

    //     $f = $tree->findAll("gar", class: re.compile("o b"));
    //     $this->assertSelects($f, ["Found it"]);
    // }

    /**
     * Undocumented function
     * Unimplemented regexp search
     *
     * @return void
     */
    // public function testFindAllWithNonDictionaryForAttrsFindsByClass(): void
    // {
    //     $soup = new Soup("<a class='bar'>Found it</a>");

    //     $this->assertSelects($soup->findAll("a", re.compile("ba")), ["Found it"]);

    //     function big_attribute_value(value): void
    //         return len(value) > 3

    //     $this->assertSelects($soup->findAll("a", big_attribute_value), []);

    //     function small_attribute_value(value): void
    //     {
    //         return len(value) <= 3
    //     }

    //     $this->assertSelects(
    //         $soup->findAll("a", small_attribute_value), ["Found it"]);
    // }

    /**
     * findAll() Use $attrs string to find multiple classes
     *
     * @return void
     */
    public function testFindAllWithStringForAttrsFindsMultipleClasses(): void
    {
        $soup = new Soup('<a class="foo bar"></a><a class="foo"></a>');
        $aTags = $soup->findAll("a");
        $a1 = $aTags[0];
        $a2 = $aTags[1];
        // TODO containsする必要がある
        // $this->assertEquals([$a1, $a2], $soup->findAll("a", "foo")->elements);
        // $this->assertEquals([$a1], $soup->findAll("a", "bar")->elements);

        $this->assertEquals([$a1], $soup->findAll("a", class: "foo bar")->elements);
        $this->assertEquals([$a1], $soup->findAll("a", "foo bar")->elements);
        $this->assertEquals([], $soup->findAll("a", "bar foo")->elements);
    }

    /**
     * Undocumented function
     * Unimplemented SoupStrainer class
     *
     * @return void
     */
    // public function testFindAllByAttributeSoupstrainer(): void
    // {
    //     $tree = new Soup('<a id="first">Match.</a>
    //         <a id="second">Non-match.</a>');

    //     strainer = SoupStrainer(attrs: {'id' : 'first'});
    //     $this->assertSelects($tree->findAll(strainer), ['Match.']);
    // }

    /**
     * findAll() If the attribute value is null, blank-valued attribute is not found.
     *
     * @return void
     */
    public function testFindAllWithMissingAttribute(): void
    {
        $tree = new Soup('<a id="1">ID present.</a>
            <a>No ID present.</a>
            <a id="">ID is empty.</a>');

        $this->assertSelects(["No ID present."], $tree->findAll('a', id: null));
    }

    /**
     * findAll() If the attribute value is true, blank-valued attribute elements are found.
     *
     * @return void
     */
    public function testFindAllWithDefinedAttribute(): void
    {
        $tree = new Soup('<a id="1">ID present.</a>
            <a>No ID present.</a>
            <a id="">ID is empty.</a>');

        $this->assertSelects(["ID present.", "ID is empty."], $tree->findAll(id: true));
    }

    /**
     * findAll() by numeric attribute value
     *
     * @return void
     */
    public function testFindAllWithNumericAttribute(): void
    {
        $tree = new Soup('<a id=1>Unquoted attribute.</a>
            <a id="1">Quoted attribute.</a>');

        $expected = ["Unquoted attribute.", "Quoted attribute."];
        $this->assertSelects($expected, $tree->findAll(id: 1));
        $this->assertSelects($expected, $tree->findAll(id: "1"));
    }

    /**
     * findAll() by array values attribute
     *
     * @return void
     */
    public function testFindAllWithListAttributeValues(): void
    {
        $tree = new Soup('
            <a id="1">1</a>
            <a id="2">2</a>
            <a id="3">3</a>
            <a>No ID.</a>');

        $this->assertSelects(['1', '3'], $tree->findAll(id: ['1', '3', '4']));
    }

    /**
     * Undocumented function
     * Unimplemented regexp
     *
     * @return void
     */
    // public function testFindAllWithRegularExpressionAttributeValue(): void
    // {
    //     // You can pass a regular expression as an attribute value, and
    //     // you'll get tags whose values for that attribute match the
    //     // regular expression.
    //     $tree = new Soup('<a id="a">One a.</a>
    //                         <a id="aa">Two as.</a>
    //                         <a id="ab">Mixed as and bs.</a>
    //                         <a id="b">One b.</a>
    //                         <a>No ID.</a>');

    //     // $this->assertSelects($tree->findAll(id=re.compile("^a+$")),
    //     //                 ["One a.", "Two as."]);
    // }

    /**
     * findAll() by $name and $text
     *
     * @return void
     */
    public function testFindByNameAndContainingString(): void
    {
        $soup = new Soup("<b>foo</b><b>bar</b><a>foo</a>");

        $a = $soup->a;
        $this->assertEquals([$a], $soup->findAll("a", text: "foo")->elements);
        $this->assertEquals([], $soup->findAll("a", text: "bar")->elements);
        $this->assertEquals([], $soup->findAll("a", text: "bar")->elements);
    }

    /**
     * findAll() by $name and $text when text is buried in a tag hierarchy
     *
     * @return void
     */
    public function testFindByNameAndContainingStringWhenStringIsBuried(): void
    {
        $soup = new Soup("<a>foo</a><a><b><c>foo</c></b></a>");

        $this->assertEquals($soup->findAll("a"), $soup->findAll("a", text: "foo"));
    }

    /**
     * findAll() by $attrs and $text
     *
     * @return void
     */
    public function testFindByAttributeAndContainingString(): void
    {
        $soup = new Soup('<b id="1">foo</b><a id="2">foo</a>');
        $a = $soup->a;

        $this->assertEquals([$a], $soup->findAll(id: 2, text: "foo")->elements);
        $this->assertEquals([], $soup->findAll(id: 1, text: "bar")->elements);
    }
}
