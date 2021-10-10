<?php

declare(strict_types=1);

namespace Eggpan\PhpSoup;

use DOMDocument;
use Eggpan\PhpSoup\Element\NavigableString;
use Eggpan\PhpSoup\Element\Tag;

/**
 * @property-read Tag|null $a
 * @property-read Tag|null $abbr
 * @property-read Tag|null $address
 * @property-read Tag|null $area
 * @property-read Tag|null $article
 * @property-read Tag|null $aside
 * @property-read Tag|null $audio
 * @property-read Tag|null $b
 * @property-read Tag|null $base
 * @property-read Tag|null $bdi
 * @property-read Tag|null $bdo
 * @property-read Tag|null $blockquote
 * @property-read Tag|null $body
 * @property-read Tag|null $br
 * @property-read Tag|null $button
 * @property-read Tag|null $canvas
 * @property-read Tag|null $caption
 * @property-read Tag|null $cite
 * @property-read Tag|null $code
 * @property-read Tag|null $col
 * @property-read Tag|null $colgroup
 * @property-read Tag|null $data
 * @property-read Tag|null $datalist
 * @property-read Tag|null $dd
 * @property-read Tag|null $del
 * @property-read Tag|null $details
 * @property-read Tag|null $dfn
 * @property-read Tag|null $dialog
 * @property-read Tag|null $div
 * @property-read Tag|null $dl
 * @property-read Tag|null $dt
 * @property-read Tag|null $em
 * @property-read Tag|null $embed
 * @property-read Tag|null $fieldset
 * @property-read Tag|null $figcaption
 * @property-read Tag|null $figure
 * @property-read Tag|null $footer
 * @property-read Tag|null $form
 * @property-read Tag|null $h1
 * @property-read Tag|null $h2
 * @property-read Tag|null $h3
 * @property-read Tag|null $h4
 * @property-read Tag|null $h5
 * @property-read Tag|null $h6
 * @property-read Tag|null $head
 * @property-read Tag|null $header
 * @property-read Tag|null $hr
 * @property-read Tag|null $html
 * @property-read Tag|null $i
 * @property-read Tag|null $iframe
 * @property-read Tag|null $img
 * @property-read Tag|null $input
 * @property-read Tag|null $ins
 * @property-read Tag|null $kbd
 * @property-read Tag|null $label
 * @property-read Tag|null $legend
 * @property-read Tag|null $li
 * @property-read Tag|null $link
 * @property-read Tag|null $main
 * @property-read Tag|null $map
 * @property-read Tag|null $mark
 * @property-read Tag|null $meta
 * @property-read Tag|null $meter
 * @property-read Tag|null $nav
 * @property-read Tag|null $noscript
 * @property-read Tag|null $object
 * @property-read Tag|null $ol
 * @property-read Tag|null $optgroup
 * @property-read Tag|null $option
 * @property-read Tag|null $output
 * @property-read Tag|null $p
 * @property-read Tag|null $param
 * @property-read Tag|null $picture
 * @property-read Tag|null $pre
 * @property-read Tag|null $progress
 * @property-read Tag|null $q
 * @property-read Tag|null $rp
 * @property-read Tag|null $rt
 * @property-read Tag|null $ruby
 * @property-read Tag|null $s
 * @property-read Tag|null $samp
 * @property-read Tag|null $script
 * @property-read Tag|null $section
 * @property-read Tag|null $select
 * @property-read Tag|null $small
 * @property-read Tag|null $source
 * @property-read Tag|null $span
 * @property-read Tag|null $strong
 * @property-read Tag|null $style
 * @property-read Tag|null $sub
 * @property-read Tag|null $summary
 * @property-read Tag|null $sup
 * @property-read Tag|null $svg
 * @property-read Tag|null $table
 * @property-read Tag|null $tbody
 * @property-read Tag|null $td
 * @property-read Tag|null $template
 * @property-read Tag|null $textarea
 * @property-read Tag|null $tfoot
 * @property-read Tag|null $th
 * @property-read Tag|null $thead
 * @property-read Tag|null $time
 * @property-read Tag|null $title
 * @property-read Tag|null $tr
 * @property-read Tag|null $track
 * @property-read Tag|null $u
 * @property-read Tag|null $ul
 * @property-read Tag|null $var
 * @property-read Tag|null $video
 * @property-read Tag|null $wbr
 */
class Soup extends HasQuery
{
    /**
     * @param string $markup Html string.
     */
    public function __construct(private string $markup)
    {
        $this->document = new DOMDocument();
        $this->document->loadHTML(mb_convert_encoding($this->markup, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return (string) preg_replace('/^[\s]+|[\s]+$/um', '', $this->document->textContent);
    }

    /**
     * @return string
     */
    public function get_text(): string
    {
        return $this->getText();
    }

    /**
     * @param string $name Property name.
     * @return Tag|NavigableString|string|null
     */
    public function __get(string $name): Tag|NavigableString|string|null
    {
        return match ($name) {
            'name'   => '[document]',
            'parent' => null,
            // TODO 要素が1つならNavigableStringを返す
            'string' => null,
            'text'   => $this->getText(),
            default  => $this->find($name),
        };
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->document->saveHTML();
    }
}
