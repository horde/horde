<?php
/**
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */

/** Horde_Text_Textile_TestCase */
require_once __DIR__ . '/TestCase.php';

/**
 * These tests correspond to "4. Attributes" from http://hobix.com/textile/.
 *
 * @category   Horde
 * @package    Text_Textile
 * @subpackage UnitTests
 */
class Horde_Text_Textile_AttributesTest extends Horde_Text_Textile_TestCase {

    public function testBlockAttributes()
    {
        $this->assertTransforms('p(example1). An example',
                                '<p class="example1">An example</p>');

        $this->assertTransforms('p(#big-red). Red here',
                                '<p id="big-red">Red here</p>');

        $this->assertTransforms('p(example1#big-red2). Red here',
                                '<p class="example1" id="big-red2">Red here</p>');

        $this->assertTransforms('p{color:blue;margin:30px}. Spacey blue',
                                '<p style="color:blue;margin:30px;">Spacey blue</p>');

        $this->assertTransforms('p[fr]. rouge',
                                '<p lang="fr">rouge</p>');
    }

    public function testPhraseAttributes()
    {
        $this->assertTransforms('I seriously *{color:red}blushed* when I _(big)sprouted_ that corn stalk from my %[es]cabeza%.',
                                '<p>I seriously <strong style="color:red;">blushed</strong> when I <em class="big">sprouted</em> that corn stalk from my <span lang="es">cabeza</span>.</p>');
    }

    public function testBlockAlignments()
    {
        $this->assertTransforms('p<. align left',
                                '<p style="text-align:left;">align left</p>');

        $this->assertTransforms('p>. align right',
                                '<p style="text-align:right;">align right</p>');

        $this->assertTransforms('p=. centered',
                                '<p style="text-align:center;">centered</p>');

        $this->assertTransforms('p<>. justified',
                                '<p style="text-align:justify;">justified</p>');

        $this->assertTransforms('p(. left ident 1em',
                                '<p style="padding-left:1em;">left ident 1em</p>');

        $this->assertTransforms('p((. left ident 2em',
                                '<p style="padding-left:2em;">left ident 2em</p>');

        $this->assertTransforms('p))). right ident 3em',
                                '<p style="padding-right:3em;">right ident 3em</p>');
    }

    public function testCombinedAlignments()
    {
        $this->assertTransforms('h2()>. Bingo.',
                                '<h2 style="padding-left:1em;padding-right:1em;text-align:right;">Bingo.</h2>');

        $this->assertTransforms('h3()>[no]{color:red}. Bingo',
                                '<h3 style="color:red;padding-left:1em;padding-right:1em;text-align:right;" lang="no">Bingo</h3>');
    }

    public function testHtml()
    {
        $this->assertTransforms('<pre>
<code>
a.gsub!( /</, \'\' )
</code>
</pre>',
                       '<pre>
<code>
a.gsub!( /&lt;/, \'\' )
</code>
</pre>');

        $text = '<div style="float:right;">

h3. Sidebar

"Hobix":http://hobix.com/ "Ruby":http://ruby-lang.org/

</div>

The main text of the page goes here and will stay to the left of the sidebar.';

        $html = '<div style="float:right;">

<h3>Sidebar</h3>

<p><a href="http://hobix.com/">Hobix</a> <a href="http://ruby-lang.org/">Ruby</a></p>

</div>

<p>The main text of the page goes here and will stay to the left of the sidebar.</p>';

        $this->assertTransforms($text, $html);
    }

}
