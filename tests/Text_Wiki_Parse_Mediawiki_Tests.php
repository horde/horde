<?php

require_once 'PHPUnit/Framework.php';
require_once 'Text/Wiki/Mediawiki.php';
require_once 'Text/Wiki/Parse/Mediawiki/Break.php';
require_once 'Text/Wiki/Parse/Mediawiki/Code.php';
require_once 'Text/Wiki/Parse/Mediawiki/Comment.php';
require_once 'Text/Wiki/Parse/Mediawiki/Deflist.php';
require_once 'Text/Wiki/Parse/Mediawiki/Emphasis.php';
require_once 'Text/Wiki/Parse/Mediawiki/Heading.php';
require_once 'Text/Wiki/Parse/Mediawiki/List.php';
require_once 'Text/Wiki/Parse/Mediawiki/Newline.php';
require_once 'Text/Wiki/Parse/Mediawiki/Preformatted.php';
require_once 'Text/Wiki/Parse/Mediawiki/Raw.php';
require_once 'Text/Wiki/Parse/Mediawiki/Subscript.php';
require_once 'Text/Wiki/Parse/Mediawiki/Superscript.php';
require_once 'Text/Wiki/Parse/Mediawiki/Table.php';
require_once 'Text/Wiki/Parse/Mediawiki/Tt.php';
require_once 'Text/Wiki/Parse/Mediawiki/Url.php';
require_once 'Text/Wiki/Parse/Mediawiki/Wikilink.php';

class Text_Wiki_Parse_Mediawiki_AllTests extends PHPUnit_Framework_TestSuite
{
    
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Text_Wiki_Render_Mediawiki_TestSuite');
        /*$suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Break_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Code_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Comment_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Deflist_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Emphasis_Test');*/
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Heading_Test');
        /*$suite->addTestSuite('Text_Wiki_Parse_Mediawiki_List_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Newline_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Preformatted_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Raw_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Subscript_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Superscript_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Table_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Tt_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Url_Test');
        $suite->addTestSuite('Text_Wiki_Parse_Mediawiki_Wikilink_Test');*/
        
        return $suite;
    }
    
}

class Text_Wiki_Parse_Mediawiki_SetUp_Tests extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $obj = Text_Wiki::singleton('Mediawiki');
        $testClassName = get_class($this);
        $ruleName = preg_replace('/Text_Wiki_Parse_Mediawiki_(.+?)_Test/', '\\1', $testClassName);
        $className = 'Text_Wiki_Parse_' . $ruleName;
        $this->t = new $className($obj);
        
        $this->fixture = file_get_contents('mediawiki_syntax.txt');
        preg_match_all($this->t->regex, $this->fixture, $this->matches);
    }

}

class Text_Wiki_Parse_Mediawiki_Heading_Test extends Text_Wiki_Parse_Mediawiki_SetUp_Tests
{
    
    public function testMediawikiParseHeadingProcess()
    {
        $matches1 = array(0 => "======Level 6 heading======\n", 1 => '======', 2 => 'Level 6 heading');
        $matches2 = array(0 => "=Level 1 heading=\n", 1 => '=', 2 => 'Level 1 heading');
        $matches3 = array(0 => "==Level 2 heading==\n", 1 => '==', 2 => 'Level 2 heading');

        $tokens = array(
            0 => array(0 => 'Heading', 1 => array('type' => 'start', 'level' => 6, 'text' => 'Level 6 heading', 'id' => 'toc0')),
            1 => array(0 => 'Heading', 1 => array('type' => 'end', 'level' => 6)),
            2 => array(0 => 'Heading', 1 => array('type' => 'start', 'level' => 1, 'text' => 'Level 1 heading', 'id' => 'toc1')),
            3 => array(0 => 'Heading', 1 => array('type' => 'end', 'level' => 1)),
            4 => array(0 => 'Heading', 1 => array('type' => 'start', 'level' => 2, 'text' => 'Level 2 heading', 'id' => 'toc2')),
            5 => array(0 => 'Heading', 1 => array('type' => 'end', 'level' => 2))
        );

        $this->assertEquals("0Level 6 heading1\n", $this->t->process($matches1));
        $this->assertEquals("2Level 1 heading3\n", $this->t->process($matches2));
        $this->assertEquals("4Level 2 heading5\n", $this->t->process($matches3));
        $this->assertEquals($tokens, $this->t->wiki->tokens);
    }
    
    public function testMediawikiParseHeadingRegex()
    {
        $expectedResult = array(
            0 => array(0 => "=Level 1 heading=\n", 1 => "==Level 2 heading==\n", 2 => "==Level 2 heading==\n", 3 => "===Level 3 heading===\n", 4 => "====Level 4 heading====\n", 5 => "===Level 3 heading===\n", 6 => "===Level 3 heading===\n", 7 => "=====Level 5 heading=====\n", 8 => "======Level 6 heading======\n"),
            1 => array(0 => '=', 1 => '==', 2 => '==', 3 => '===', 4 => '====', 5 => '===', 6 => '===', 7 => '=====', 8 => '======'),
            2 => array(0 => 'Level 1 heading', 1 => 'Level 2 heading', 2 => 'Level 2 heading', 3 => 'Level 3 heading', 4 => 'Level 4 heading', 5 => 'Level 3 heading', 6 => 'Level 3 heading', 7 => 'Level 5 heading', 8 => 'Level 6 heading')
        );
        $this->assertEquals($expectedResult, $this->matches);
    }
    
}

?>