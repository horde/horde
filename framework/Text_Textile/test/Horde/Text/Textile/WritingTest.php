<?php
/**
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */

/** Horde_Text_Textile_TestCase */
require_once dirname(__FILE__) . '/TestCase.php';

/**
 * These tests correspond to "1. Writing in Textile" from http://hobix.com/textile/.
 *
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */
class Horde_Text_Textile_WritingTest extends Horde_Text_Textile_TestCase {

    public function testParagraphs()
    {
        $text = "A single paragraph.

Followed by another.";
        $html = "<p>A single paragraph.</p>

<p>Followed by another.</p>";

        $this->assertTransforms($text, $html);
    }

    public function testHtml()
    {
        $text = "I am <b>very</b> serious.

<pre>
I am <b>very</b> serious.
</pre>";
        $html = "<p>I am <b>very</b> serious.</p>

<pre>
I am &lt;b&gt;very&lt;/b&gt; serious.
</pre>";

        $this->assertTransforms($text, $html);
    }

    public function testLinebreaks()
    {
        $text = "I spoke.
And none replied.";
        $html = "<p>I spoke.<br />
And none replied.</p>";

        $this->assertTransforms($text, $html);
    }

    public function testEntities()
    {
        $this->assertTransforms('"Observe!"', '<p>&#8220;Observe!&#8221;</p>', 'Curly Quotes');
        $this->assertTransforms('Observe -- very nice!', '<p>Observe&#8212;very nice!</p>', 'Em Dash');
        $this->assertTransforms('Observe - tiny and brief.', '<p>Observe &#8211; tiny and brief.</p>', 'En Dash');
        $this->assertTransforms('Observe...', '<p>Observe&#8230;</p>', 'Ellipsis');
        $this->assertTransforms('Observe: 2 x 2.', '<p>Observe: 2&#215;2.</p>', 'Dimension');
        $this->assertTransforms('one(TM), two(R), three(C).', '<p>one&#8482;, two&#174;, three&#169;.</p>', 'Trademark, Registered, Copyright');
    }

}
