<?php

require_once 'PHPUnit/Framework.php';
require_once 'Text/Wiki/Tiki.php';

class Text_Wiki_Render_Tiki_Test extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $this->t = new Text_Wiki_Tiki();
    }

    protected function tearDown()
    {
        unset($this->t);
    }

    public function testTikiRenderUrl()
    {
        // TODO: solve the paragraph on last line issue and remove the \n\n from all assertEquals()
        $this->assertEquals("[http://test.com]\n\n", $this->t->transform('http://test.com', 'Tiki'));
        $this->assertEquals("[http://test.com/page.php]\n\n", $this->t->transform('http://test.com/page.php', 'Tiki'));
        $this->assertEquals("[http://test.com]\n\n", $this->t->transform('[http://test.com]', 'Tiki'));
        $this->assertEquals("[http://test.com/page.php]\n\n", $this->t->transform('[http://test.com/page.php]', 'Tiki'));
        $this->assertEquals("[http://test.com|Example Url]\n\n", $this->t->transform('[http://test.com|Example Url]', 'Tiki'));
        $this->assertEquals("[http://test.com/index.php#anchor|Example Url]\n\n", $this->t->transform('[http://test.com/index.php#anchor|Example Url]', 'Tiki'));
    }

    public function testTikiRenderWikilink()
    {
        $this->assertEquals("((WikiLink))\n\n", $this->t->transform('((WikiLink))', 'Tiki'));
        $this->assertEquals("((WikiLink|An example page))\n\n", $this->t->transform('((WikiLink|An example page))', 'Tiki'));
        $this->assertEquals("((WikiLink with spaces))\n\n", $this->t->transform('((WikiLink with spaces))', 'Tiki'));
        $this->assertEquals("((WikiLink with spaces and alternative name|Other name))\n\n", $this->t->transform('((WikiLink with spaces and alternative name|Other name))', 'Tiki'));
    }

    public function testTikiRenderWikilinkSingleTokenSyntax()
    {
        require_once 'Text/Wiki/Render/Tiki/Wikilink.php';
        $wl = new Text_Wiki_Render_Tiki_Wikilink($this->t);
        $options = array('page' => 'Test page', 'text' => 'Alternative text');
        $this->assertEquals("((Test page|Alternative text))", $wl->token($options));
    }

    public function testTikiRenderWikilinkMultiTokenSyntax()
    {
        require_once 'Text/Wiki/Render/Tiki/Wikilink.php';
        $wl = new Text_Wiki_Render_Tiki_Wikilink($this->t);
        $options = array('type' => 'start', 'page' => 'Test page', 'text' => 'Alternative text');
        $this->assertEquals('((Test page|', $wl->token($options));
        $options = array('type' => 'end');
        $this->assertEquals('))', $wl->token($options));
    }

    public function testTikiRenderParagraph()
    {
        $this->assertEquals("Some text in the first paragraph\n\nSome text in the second paragraph\n\nMore text\n\n", $this->t->transform("Some text in the first paragraph\n\nSome text in the second paragraph\n\nMore text", 'Tiki'));
    }
    
    public function testTikiRenderPreformatted()
    {
        $this->assertEquals("~pp~Some text~/pp~\n\n", $this->t->transform('~pp~Some text~/pp~', 'Tiki'));
    }

    public function testTikiRenderItalic()
    {
        $this->assertEquals("''italic''\n\n", $this->t->transform("''italic''", 'Tiki'));
    }

}

?>