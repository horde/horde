<?php

require_once('PHPUnit/Framework.php');
require_once('Text/Wiki/Tiki.php');

class Text_Wiki_Parser_Tiki_Test extends PHPUnit_Framework_TestCase {

    protected function setUp()
    {
        $this->t = Text_Wiki::factory('Tiki');
    }

    protected function tearDown()
    {
        unset($this->t);
    }

    public function testTikiParseUrl()
    {
        $this->t->parse('[http://www.example.com/page|An example page] some text that is not an URL [http://www.example.com/page.php#anchor|Other example page]');
        $this->assertEquals('Paragraph', $this->t->tokens[0][0]);
        $this->assertEquals('start', $this->t->tokens[0][1]['type']);
        $this->assertEquals('Paragraph', $this->t->tokens[1][0]);
        $this->assertEquals('end', $this->t->tokens[1][1]['type']);
        $this->assertEquals('Url', $this->t->tokens[2][0]);
        $this->assertEquals('descr', $this->t->tokens[2][1]['type']);
        $this->assertEquals('http://www.example.com/page', $this->t->tokens[2][1]['href']);
        $this->assertEquals('An example page', $this->t->tokens[2][1]['text']);
        $this->assertEquals('http://www.example.com/page.php#anchor', $this->t->tokens[3][1]['href']);
        $this->assertEquals('Other example page', $this->t->tokens[3][1]['text']);
    }

}

?>