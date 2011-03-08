<?php
/**
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */

/** Horde_Text_Textile_TestCase */
require_once dirname(__FILE__) . '/TestCase.php';

/**
 * These tests correspond to "6. External References" from http://hobix.com/textile/.
 *
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */
class Horde_Text_Textile_ExternalReferencesTest extends Horde_Text_Textile_TestCase {

    public function testHypertextLinks()
    {
        $this->assertTransforms('I searched "Google":http://google.com.',
                       '<p>I searched <a href="http://google.com">Google</a>.</p>');
    }

    public function testLinkAliases()
    {
        $this->assertTransforms('I am crazy about "Hobix":hobix and "it\'s":hobix "all":hobix I ever "link to":hobix!

[hobix]http://hobix.com',
                       '<p>I am crazy about <a href="http://hobix.com">Hobix</a> and <a href="http://hobix.com">it&#8217;s</a> <a href="http://hobix.com">all</a> I ever <a href="http://hobix.com">link to</a>!</p>

');
    }

    public function testEmbeddedImages()
    {
        $this->assertTransforms('!http://hobix.com/sample.jpg!',
                                '<p><img src="http://hobix.com/sample.jpg" alt="" /></p>');

        $this->assertTransforms('!openwindow1.gif(Bunny.)!',
                                '<p><img src="openwindow1.gif" title="Bunny." alt="Bunny." /></p>');

        $this->assertTransforms('!openwindow1.gif!:http://hobix.com/',
                                '<p><a href="http://hobix.com/"><img src="openwindow1.gif" alt="" /></a></p>');
    }

    public function testImageAlignments()
    {
        $this->assertTransforms('!>obake.gif!

And others sat all round the small machine and paid it to sing to them.',
                       '<p><img src="obake.gif" align="right" alt="" /></p>

<p>And others sat all round the small machine and paid it to sing to them.</p>');
    }

    public function testAcronyms()
    {
        $this->assertTransforms('We use CSS(Cascading Style Sheets).',
                                '<p>We use <acronym title="Cascading Style Sheets">CSS</acronym>.</p>');
    }

}
