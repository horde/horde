<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Text/Wiki/Tiki.php';
require_once 'Text/Wiki/Render/Tiki.php';
require_once 'Text/Wiki/Render/Tiki/Anchor.php';
require_once 'Text/Wiki/Render/Tiki/Blockquote.php';
require_once 'Text/Wiki/Render/Tiki/Bold.php';
require_once 'Text/Wiki/Render/Tiki/Box.php';
require_once 'Text/Wiki/Render/Tiki/Break.php';
require_once 'Text/Wiki/Render/Tiki/Center.php';
require_once 'Text/Wiki/Render/Tiki/Code.php';
require_once 'Text/Wiki/Render/Tiki/Colortext.php';
require_once 'Text/Wiki/Render/Tiki/Deflist.php';
require_once 'Text/Wiki/Render/Tiki/Delimiter.php';
require_once 'Text/Wiki/Render/Tiki/Embed.php';
require_once 'Text/Wiki/Render/Tiki/Emphasis.php';
require_once 'Text/Wiki/Render/Tiki/Freelink.php';
require_once 'Text/Wiki/Render/Tiki/Function.php';
require_once 'Text/Wiki/Render/Tiki/Heading.php';
require_once 'Text/Wiki/Render/Tiki/Horiz.php';
require_once 'Text/Wiki/Render/Tiki/Html.php';
require_once 'Text/Wiki/Render/Tiki/Image.php';
require_once 'Text/Wiki/Render/Tiki/Include.php';
require_once 'Text/Wiki/Render/Tiki/Interwiki.php';
require_once 'Text/Wiki/Render/Tiki/Italic.php';
require_once 'Text/Wiki/Render/Tiki/List.php';
require_once 'Text/Wiki/Render/Tiki/Newline.php';
require_once 'Text/Wiki/Render/Tiki/Paragraph.php';
require_once 'Text/Wiki/Render/Tiki/Phplookup.php';
require_once 'Text/Wiki/Render/Tiki/Prefilter.php';
require_once 'Text/Wiki/Render/Tiki/Preformatted.php';
require_once 'Text/Wiki/Render/Tiki/Raw.php';
require_once 'Text/Wiki/Render/Tiki/Redirect.php';
require_once 'Text/Wiki/Render/Tiki/Revise.php';
require_once 'Text/Wiki/Render/Tiki/Strong.php';
require_once 'Text/Wiki/Render/Tiki/Subscript.php';
require_once 'Text/Wiki/Render/Tiki/Superscript.php';
require_once 'Text/Wiki/Render/Tiki/Table.php';
require_once 'Text/Wiki/Render/Tiki/Tighten.php';
require_once 'Text/Wiki/Render/Tiki/Toc.php';
require_once 'Text/Wiki/Render/Tiki/Tt.php';
require_once 'Text/Wiki/Render/Tiki/Underline.php';
require_once 'Text/Wiki/Render/Tiki/Url.php';
require_once 'Text/Wiki/Render/Tiki/Wikilink.php';

class Text_Wiki_Render_Tiki_AllTests extends PHPUnit_Framework_TestSuite
{
    
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Text_Wiki_Render_Tiki_TestSuite');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Anchor_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Blockquote_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Bold_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Box_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Break_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Center_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Code_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Colortext_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Deflist_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Delimiter_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Embed_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Emphasis_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Freelink_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Function_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Heading_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Horiz_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Html_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Image_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Include_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Interwiki_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Italic_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_List_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Newline_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Paragraph_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Phplookup_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Prefilter_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Preformatted_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Raw_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Redirect_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Revise_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Strong_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Subscript_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Superscript_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Table_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Tighten_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Toc_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Tt_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Underline_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Url_Test');
        $suite->addTestSuite('Text_Wiki_Render_Tiki_Wikilink_Test');
        
        return $suite;
    }
    
}

class Text_Wiki_Render_Tiki_Test extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $obj = Text_Wiki::singleton('Tiki');
        $this->t = new Text_Wiki_Render_Tiki($obj);
    }

    public function testTikiRenderPre()
    {
        $this->assertEquals('', $this->t->pre());
    }

    public function testTikiRenderPost()
    {
        $this->assertEquals('', $this->t->post());
    }

}

class Text_Wiki_Render_Tiki_SetUp_Tests extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $obj = Text_Wiki::singleton('Tiki');
        $testClassName = get_class($this);
        $ruleName = preg_replace('/Text_Wiki_Render_Tiki_(.+?)_Test/', '\\1', $testClassName);
        $className = 'Text_Wiki_Render_Tiki_' . $ruleName;
        $this->t = new $className($obj);
    }

}

class Text_Wiki_Render_Tiki_Anchor_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderAnchor()
    {
        $options = array('type' => 'start', 'name' => 'Page name');
        $this->assertEquals('((Page name', $this->t->token($options));
        $options = array('type' => 'end');
        $this->assertEquals('))', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Blockquote_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderBlockquote()
    {
        $this->markTestIncomplete('check if Text_Wiki_Render_Tiki_Blockquote output a valid Tiki syntax.');
    }
    
}

class Text_Wiki_Render_Tiki_Bold_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderBold()
    {
        $options = array();
        $this->assertEquals('__', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Box_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderBox()
    {
        $options = array('type' => 'start');
        $this->assertEquals('^', $this->t->token($options));
        $options = array('type' => 'end');
        $this->assertEquals('^', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Break_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderBreak()
    {
        $options = array();
        $this->assertEquals("\n", $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Center_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderCenter()
    {
        $options = array('type' => 'start');
        $this->assertEquals('::', $this->t->token($options));
        $options = array('type' => 'end');
        $this->assertEquals('::', $this->t->token($options));
    }
    
}


class Text_Wiki_Render_Tiki_Code_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderCode()
    {
        $options = array('text' => 'Some code text as a sample');
        $this->assertEquals("{CODE()}\nSome code text as a sample\n{CODE}", $this->t->token($options));
        $options = array('text' => 'Some code text as a sample', 'attr' => array('type' => ''));
        $this->assertEquals("{CODE()}\nSome code text as a sample\n{CODE}", $this->t->token($options));
        $options = array('text' =>  'Some code text as a sample', 'attr' => array('type' => 'php'));
        $this->assertEquals("{CODE(colors=>php)}\nSome code text as a sample\n{CODE}", $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Colortext_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderColortext()
    {
        $options = array('type' => 'start', 'color' => 'red');
        $this->assertEquals('~~red:', $this->t->token($options));
        $options = array('type' => 'start', 'color' => 'FFFFFF');
        $this->assertEquals('~~#FFFFFF:', $this->t->token($options));
        $options = array('type' => 'end');
        $this->assertEquals('~~', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Deflist_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderDeflist()
    {
        $options = array('type' => 'list_start');
        $this->assertEquals("{DL()}\n", $this->t->token($options));
        $options = array('type' => 'list_end');
        $this->assertEquals("{DL}\n\n", $this->t->token($options));
        $options = array('type' => 'term_start');
        $this->assertEquals('', $this->t->token($options));
        $options = array('type' => 'term_end');
        $this->assertEquals(': ', $this->t->token($options));
        $options = array('type' => 'narr_start');
        $this->assertEquals('', $this->t->token($options));
        $options = array('type' => 'narr_end');
        $this->assertEquals("\n", $this->t->token($options));

        // test definition item without definition narrative
        $this->t->token(array('type' => 'term_end'));
        $this->assertEquals('term_end', $this->t->last);
        $options = array('type' => 'term_start');
        $this->assertEquals("\n", $this->t->token($options));
        $this->assertEquals('term_start', $this->t->last);

        // test default swicth behavior
        $options = array('type' => 'InvalidType');
        $this->assertEquals('', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Delimiter_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderDelimiter()
    {
        $options = array('text' => 'Sample text');
        $this->assertEquals('Sample text', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Embed_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderEmbed()
    {
        $options = array('text' => 'Sample text');
        $this->assertEquals('Sample text', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Emphasis_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderEmphasis()
    {
        $options = array();
        $this->assertEquals("''", $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Freelink_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{    
    
    public function testTikiRenderFreelink()
    {
        $options = array('type' => 'start', 'page' => 'Sample page', 'text' => 'Sample text');
        $this->assertEquals('((Sample page|', $this->t->token($options));
        $options = array('type' => 'end');
        $this->assertEquals('))', $this->t->token($options));
        $options = array('page' => 'Sample page', 'text' => 'Sample text');
        $this->assertEquals('((Sample page|Sample text))', $this->t->token($options));
    }
       
}

class Text_Wiki_Render_Tiki_Function_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{    
    
    public function testTikiRenderFunction()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
       
}

class Text_Wiki_Render_Tiki_Heading_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{    
    
    public function testTikiRenderHeading()
    {
        $options = array('type' => 'start', 'level' => 1);
        $this->assertEquals("!", $this->t->token($options));
        $options = array('type' => 'start', 'level' => 2);
        $this->assertEquals("!!", $this->t->token($options));
        $options = array('type' => 'start', 'level' => 6);
        $this->assertEquals("!!!!!!", $this->t->token($options));
        $options = array('type' => 'end');
        $this->assertEquals("\n", $this->t->token($options));
    }
       
}

class Text_Wiki_Render_Tiki_Horiz_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{    
    
    public function testTikiRenderHoriz()
    {
        $options = array();
        $this->assertEquals("\n---\n", $this->t->token($options));
    }
       
}

class Text_Wiki_Render_Tiki_Html_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderHtml()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
    
}

class Text_Wiki_Render_Tiki_Image_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderImage()
    {
        $options = array('src' => 'src/image.jpg');
        $this->assertEquals('{img src="img/wiki_up/src/image.jpg"}', $this->t->token($options));
        $options = array('src' => 'src/image.jpg', 'attr' => array());
        $this->assertEquals('{img src="img/wiki_up/src/image.jpg"}', $this->t->token($options));
        $options = array('src' => 'src/image.jpg', 'attr' => array('width' => 600, 'height' => 500));
        $this->assertEquals('{img src="img/wiki_up/src/image.jpg" width="600" height="500"}', $this->t->token($options));

        $this->t->conf = array('prefix' => 'different/path/');
        $options = array('src' => 'image.jpg');
        $this->assertEquals('{img src="different/path/image.jpg"}', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Include_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderInclude()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
    
}

class Text_Wiki_Render_Tiki_Interwiki_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderInterwiki()
    {
        $this->markTestIncomplete('Check if Text_Wiki_Render_Tiki_Interwiki output a valid Tiki syntax.');
        $options = array('site' => 'doc.tikiwiki.org', 'page' => 'WikiSyntax');
        $this->assertEquals('((doc.tikiwiki.org:WikiSyntax))', $this->t->token($options));
        $options = array('site' => 'doc.tikiwiki.org', 'page' => 'WikiSyntax', 'text' => 'Page WikiSyntax from doc.tikiwiki.org');
        $this->assertEquals('((doc.tikiwiki.org:WikiSyntax|Page WikiSyntax from doc.tikiwiki.org))', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Italic_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderItalic()
    {
        $options = array();
        $this->assertEquals("''", $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_List_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderNumberItemStart()
    {
        $options = array('type' => 'number_item_start', 'level' => 1);
        $this->assertEquals("#", $this->t->token($options));
        $options = array('type' => 'number_item_start', 'level' => 3);
        $this->assertEquals("###", $this->t->token($options));
    }

    public function testTikiRenderBulletItemStart()    
    {
        $options = array('type' => 'bullet_item_start', 'level' => 1);
        $this->assertEquals("*", $this->t->token($options));
        $options = array('type' => 'bullet_item_start', 'level' => 3);
        $this->assertEquals("***", $this->t->token($options));
    }

    public function testTikiRenderBulletAndNumberedListEnd()
    {
        $options = array('type' => 'bullet_list_end', 'level' => 0);
        $this->assertEquals("\n", $this->t->token($options));
        $options = array('type' => 'number_list_end', 'level' => 0);
        $this->assertEquals("\n", $this->t->token($options));
        $options = array('type' => 'bullet_list_end', 'level' => 1);
        $this->assertEquals("", $this->t->token($options));
        $options = array('type' => 'number_list_end', 'level' => 1);
        $this->assertEquals("", $this->t->token($options));
    }

    public function testTikiRenderBulletAndNumberedListStart()
    {
        $options = array('type' => 'bullet_list_start');
        $this->assertEquals("", $this->t->token($options));
        $options = array('type' => 'number_list_start');
        $this->assertEquals("", $this->t->token($options));
    }

    public function testTikiRenderBullerAndNumberedItemEnd()
    {
        $options = array('type' => 'bullet_item_end');
        $this->assertEquals("\n", $this->t->token($options));
        $options = array('type' => 'number_item_end');
        $this->assertEquals("\n", $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Newline_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderNewline()
    {
        $options = array();
        $this->assertEquals("\n", $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Paragraph_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderParagraph()
    {
        $options = array('type' => 'start');
        $this->assertEquals('', $this->t->token($options));
        $options = array('type' => 'end');
        $this->assertEquals("\n\n", $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Phplookup_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderPhplookup()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
    
}

class Text_Wiki_Render_Tiki_Prefilter_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderPrefilter()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
    
}

class Text_Wiki_Render_Tiki_Preformatted_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderPreformatted()
    {
        $options = array('text' => 'Some preformatted text');
        $this->assertEquals('~pp~Some preformatted text~/pp~', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Raw_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderRaw()
    {
        $options = array('text' => 'Some raw text');
        $this->assertEquals('~np~Some raw text~/np~', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Redirect_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderRedirect()
    {
        $options = array('type' => 'start', 'text' => 'Some wiki link');
        $this->assertEquals('{redirect page="', $this->t->token($options));

        $options = array('type' => 'end');
        $this->assertEquals('"}', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Revise_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderRevise()
    {
        $this->markTestIncomplete('Check if Text_Wiki_Render_Tiki_Revise output a valid Tiki syntax.');
    }
    
}

class Text_Wiki_Render_Tiki_Strong_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderStrong()
    {
        $options = array();
        $this->assertEquals('__', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Subscript_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderSubscript()
    {
        $this->markTestIncomplete('Check if Text_Wiki_Render_Tiki_Subscript output a valid Tiki syntax.');
    }
    
}

class Text_Wiki_Render_Tiki_Superscript_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderSuperscript()
    {
        $this->markTestIncomplete('Check if Text_Wiki_Render_Tiki_Superscript output a valid Tiki syntax.');
    }
    
}

class Text_Wiki_Render_Tiki_Table_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderTable()
    {
        /* test cases that doesn't depend on the static variable $last */
        $options = array('type' => 'table_start');
        $this->assertEquals('||', $this->t->token($options));

        $options = array('type' => 'table_end');
        $this->assertEquals('||', $this->t->token($options));

        $options = array('type' => 'row_end');
        $this->assertEquals('', $this->t->token($options));

        $options = array('type' => 'cell_end', 'span' => 1);
        $this->assertEquals('', $this->t->token($options));

        $options = array('type' => 'cell_end', 'span' => 4);
        $this->assertEquals(' |  |  | ', $this->t->token($options));

        $options = array('type' => 'cell_end');
        $this->assertEquals('', $this->t->token($options));
    }
    
    public function testTikiRenderTableDependLastVariable()
    {
        /* test cases that depend on the static variable $last. we run token()
           with a different type first to set the appropiate $last value and then 
           we run it again we the desirable assert value */
        $options = array('type' => 'table_start');
        $this->t->token($options);
        $options = array('type' => 'row_start');
        $this->assertEquals('', $this->t->token($options));


        $options = array('type' => 'cell_end');
        $this->t->token($options);
        $options = array('type' => 'cell_start');
        $this->assertEquals(' | ', $this->t->token($options));
    }

}

class Text_Wiki_Render_Tiki_Tighten_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderTighten()
    {
        $options = array();
        $this->assertEquals('', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Toc_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderToc()
    {
        $options = array();
        $this->assertEquals("\n{maketoc}\n", $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_tt_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRendertt()
    {
        $this->markTestIncomplete('Check if Text_Wiki_Render_Tiki_tt output a valid Tiki syntax.');
    }
    
}

class Text_Wiki_Render_Tiki_Underline_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderUnderline()
    {
        $options = array();
        $this->assertEquals('===', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Url_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderUrlMultiToken()
    {
        $options = array('type' => 'start', 'href' => 'http://example.com');
        $this->assertEquals('[http://example.com', $this->t->token($options));
        $options = array('type' => 'start', 'href' => 'http://example.com', 'text' => 'http://example.com');
        $this->assertEquals('[http://example.com', $this->t->token($options));
        $options = array('type' => 'start', 'href' => 'http://example.com', 'text' => 'Sample text');
        $this->assertEquals('[http://example.com|', $this->t->token($options));
        $options = array('type' => 'end');
        $this->assertEquals(']', $this->t->token($options));
    }
    
    public function testTikiRenderUrlSingleToken()
    {
        $options = array('href' => 'http://example.com');
        $this->assertEquals('[http://example.com]', $this->t->token($options));
        $options = array( 'href' => 'http://example.com', 'text' => 'http://example.com');
        $this->assertEquals('[http://example.com]', $this->t->token($options));
        $options = array( 'href' => 'http://example.com', 'text' => 'Sample text');
        $this->assertEquals('[http://example.com|Sample text]', $this->t->token($options));
    }
    
}

class Text_Wiki_Render_Tiki_Wikilink_Test extends Text_Wiki_Render_Tiki_SetUp_Tests
{
    
    public function testTikiRenderWikilinkMultiToken()
    {
        $options = array('type' => 'start', 'page' => 'Sample page', 'text' => 'Sample text');
        $this->assertEquals('((Sample page|', $this->t->token($options));
        $options = array('type' => 'start', 'page' => 'Sample page', 'text' => 'Sample page');
        $this->assertEquals('((Sample page|', $this->t->token($options));
        $options = array('type' => 'end');
        $this->assertEquals('))', $this->t->token($options));
    }

    public function testTikiRenderWikilinkSingleToken()
    {
        $options = array('page' => 'Sample page');
        $this->assertEquals('((Sample page))', $this->t->token($options));
        $options = array('page' => 'Sample page', 'text' => 'Sample text');
        $this->assertEquals('((Sample page|Sample text))', $this->t->token($options));
        $options = array('page' => 'Sample page', 'text' => 'Sample page');
        $this->assertEquals('((Sample page))', $this->t->token($options));
    }
    
}

?>
