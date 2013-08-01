<?php
/**
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */

/** Horde_Text_Textile_TestCase */
require_once __DIR__ . '/TestCase.php';

/**
 * These tests correspond to "3. Quick Phrase Modifiers" from http://hobix.com/textile/.
 *
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */
class Horde_Text_Textile_PhraseModifiersTest extends Horde_Text_Textile_TestCase {

    public function testStructuralEmphasis()
    {
        $this->assertTransforms('I _believe_ every word.',
                                '<p>I <em>believe</em> every word.</p>');

        $this->assertTransforms('And then? She *fell*!',
                                '<p>And then? She <strong>fell</strong>!</p>');

        $this->assertTransforms("I __know__.
I **really** __know__.",
                       "<p>I <i>know</i>.<br />
I <b>really</b> <i>know</i>.</p>");

        $this->assertTransforms('??Cat\'s Cradle?? by Vonnegut',
                                '<p><cite>Cat&#8217;s Cradle</cite> by Vonnegut</p>',
                                'Citation');

        $this->assertTransforms('Convert with @r.to_html@',
                                '<p>Convert with <code>r.to_html</code></p>',
                                'Code');

        $this->assertTransforms('I\'m -sure- not sure.',
                                '<p>I&#8217;m <del>sure</del> not sure.</p>',
                                'Deleted Text');

        $this->assertTransforms('You are a +pleasant+ child.',
                                '<p>You are a <ins>pleasant</ins> child.</p>',
                                'Inserted Text');

        $this->assertTransforms('a ^2^ + b ^2^ = c ^2^',
                                '<p>a <sup>2</sup> + b <sup>2</sup> = c <sup>2</sup></p>',
                                'Superscript');

        $this->assertTransforms('log ~2~ x',
                                '<p>log <sub>2</sub> x</p>',
                                'Subscript');
    }

    public function testHtmlAttributes()
    {
        $this->assertTransforms('I\'m %unaware% of most soft drinks.',
                                '<p>I&#8217;m <span>unaware</span> of most soft drinks.</p>');

        $this->assertTransforms('I\'m %{color:red}unaware% of most soft drinks.',
                                '<p>I&#8217;m <span style="color:red;">unaware</span> of most soft drinks.</p>');
    }

}
