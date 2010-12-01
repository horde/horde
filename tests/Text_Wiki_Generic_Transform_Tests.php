<?php

require_once 'PHPUnit/Framework.php';
require_once 'Text/Wiki.php';

// class to test the Text_Wiki::transform() with different wiki markups
class Text_Wiki_Generic_Transform_Tests extends PHPUnit_Framework_TestCase
{

    public function testTransformFromMediawikiToTiki()
    {
        $obj = Text_Wiki::factory('Mediawiki');
        $obj->parseConf['Wikilink']['spaceUnderscore'] = false;
        $source = file_get_contents(dirname(__FILE__) . '/fixtures/test_mediawiki_to_tiki_source.txt');
        $expectedResult = file_get_contents(dirname(__FILE__) . '/fixtures/test_mediawiki_to_tiki_output.txt');
        $this->assertEquals($expectedResult, $obj->transform($source, 'Tiki'));
    }

    public function testTransformFromMediawikiToTikiListSyntax()
    {
        $obj = Text_Wiki::factory('Mediawiki');
        $obj->parseConf['Wikilink']['spaceUnderscore'] = false;
        $source = file_get_contents(dirname(__FILE__) . '/fixtures/test_mediawiki_to_tiki_lists_source.txt');
        $expectedResult = file_get_contents(dirname(__FILE__) . '/fixtures/test_mediawiki_to_tiki_lists_output.txt');
        $this->assertEquals($expectedResult, $obj->transform($source, 'Tiki'));
    }
    
    public function testTransformFromMediawikiToTikiRedirectSyntax()
    {
        $obj = Text_Wiki::factory('Mediawiki');
        $obj->parseConf['Wikilink']['spaceUnderscore'] = false;
        $source = file_get_contents(dirname(__FILE__) . '/fixtures/test_mediawiki_to_tiki_redirect_source.txt');
        $expectedResult = file_get_contents(dirname(__FILE__) . '/fixtures/test_mediawiki_to_tiki_redirect_output.txt');
        $this->assertEquals($expectedResult, $obj->transform($source, 'Tiki'));
    }

}

?>
