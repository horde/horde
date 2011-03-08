<?php
/**
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */

/** Horde_Text_Textile_TestCase */
require_once dirname(__FILE__) . '/TestCase.php';

/**
 * These tests correspond to "2. Quick Block Modifiers" from http://hobix.com/textile/.
 *
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */
class Horde_Text_Textile_BlockModifiersTest extends Horde_Text_Textile_TestCase {

    public function testHeaders()
    {
        $this->assertTransforms('h1. Header 1', '<h1>Header 1</h1>', 'H1');
        $this->assertTransforms('h2. Header 2', '<h2>Header 2</h2>', 'H2');
        $this->assertTransforms('h3. Header 3', '<h3>Header 3</h3>', 'H3');
    }

    public function testBlockQuotes()
    {
        $text = "An old text

bq. A block quotation.

Any old text";
        $html = "<p>An old text</p>

<blockquote>
<p>A block quotation.</p>
</blockquote>

<p>Any old text</p>";

        $this->assertTransforms($text, $html);
    }

    public function testFootnotes()
    {
        $text = 'This is covered elsewhere[1].';
        $html = '<p>This is covered elsewhere<sup><a id="fnr1" href="#fn1">1</a></sup>.</p>';
        $this->assertTransforms($text, $html);

        $text = 'fn1. Down here, in fact.';
        $html = '<p id="fn1"><sup>1</sup> Down here, in fact. <a href="#fnr1">&#8617;</a></p>';
        $this->assertTransforms($text, $html);
    }

}
