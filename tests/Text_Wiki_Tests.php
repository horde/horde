<?php

require_once 'PHPUnit/Framework.php';
require_once 'Text/Wiki.php';

class Text_Wiki_Tests extends PHPUnit_Framework_TestCase
{
    
    function setUp()
    {
        $this->obj = Text_Wiki::factory();
        $this->obj->renderConf = array();
        $this->obj->parseConf = array();
    }
    
    function testSingletonOfSameParserShouldReturnSameObject()
    {
        $obj1 = Text_Wiki::singleton();
        $obj2 = Text_Wiki::singleton();      
        $this->assertEquals(spl_object_hash($obj1), spl_object_hash($obj2));
    }
    
    function testSingletonOfDifferentParserShouldReturnDifferentObject()
    {
        $obj1 = Text_Wiki::singleton('Tiki');
        $obj2 = Text_Wiki::singleton();
        $this->assertNotEquals(spl_object_hash($obj1), spl_object_hash($obj2));
    }
    
    function testFactoryReturnDefaultParserInstance()
    {
        $obj = Text_Wiki::factory();
        $this->assertTrue(is_a($obj, 'Text_Wiki_Default'));
    }
    
    function testFactoryRestrictRulesUniverse()
    {
        $rules = array('Heading', 'Bold', 'Italic', 'Paragraph');
        $obj = Text_Wiki::factory('Default', $rules);
        $this->assertEquals($rules, $obj->rules);
    }

    function testSetParseConf()
    {
        $expectedResult = array('Center' => array('css' => 'center'));
        $this->obj->setParseConf('center', 'css', 'center');
        $this->assertEquals($expectedResult, $this->obj->parseConf);
        
        $this->obj->parseConf = array();
        $expectedResult = array('Center' => array('css' => 'center'));
        $this->obj->setParseConf('center', array('css' => 'center'));
        $this->assertEquals($expectedResult, $this->obj->parseConf);
    }

    function testGetParseConf()
    {
        $this->obj->parseConf = array('Include' => array('base' => '/path/to/scripts/',
                                                         'anotherKey' => 'anotherValue'),
                                      'Secondrule' => array('base' => '/other/path/'));
        
        $this->assertEquals(array('base' => '/other/path/'), $this->obj->getParseConf('Secondrule'));
        $this->assertEquals('/path/to/scripts/', $this->obj->getParseConf('include', 'base'));
    }
    
    function testSetRenderConf()
    {
        $this->obj->setRenderConf('xhtml', 'center', 'css', 'center');
        $expectedResult = array('Center' => array('css' => 'center'));
        $this->assertEquals($expectedResult, $this->obj->renderConf['Xhtml']);
        
        $this->obj->setRenderConf('xhtml', 'center', 'secondConfig', 'secondConfigValue');
        $expectedResult = array('Center' => array('css' => 'center', 'secondConfig' => 'secondConfigValue'));
        $this->assertEquals($expectedResult, $this->obj->renderConf['Xhtml']);

        $arg = array('firstConfig' => 'firstConfigValue', 'secondConfig' => 'diferentValue');
        $this->obj->setRenderConf('xhtml', 'newrule', $arg);
        $expectedResult = array_merge($expectedResult, array('Newrule' => $arg));
        $this->assertEquals($expectedResult, $this->obj->renderConf['Xhtml']);
    }

    function testGetRenderConfReturnRule()
    {
        $this->obj->renderConf['Xhtml'] = array('Center' => array('css' => 'center', 'align' => 'left')); 
        $this->assertEquals(array('css' => 'center', 'align' => 'left'), $this->obj->getRenderConf('Xhtml', 'Center'));
    }
    
    function testGetRenderConfReturnEspecifKeyRule()
    {
        $this->obj->renderConf['Xhtml'] = array('Center' => array('css' => 'center', 'align' => 'left')); 
        $this->assertEquals('center', $this->obj->getRenderConf('Xhtml', 'Center', 'css'));
    }
    
    function testGetRenderConfReturnFalseForInvalidFormatOrConfOrKey()
    {
        $this->obj->renderConf['Xhtml'] = array('Center' => array('css' => 'center', 'align' => 'left')); 
        $this->assertNull($this->obj->getRenderConf('InvalidFormat', 'InvalidRule', 'InvalidKey'));
        $this->assertNull($this->obj->getRenderConf('Xhtml', 'InvalidRule'));
        $this->assertNull($this->obj->getRenderConf('Xhtml', 'Center', 'InvalidKey'));
    }

    public function testSetFormatConf()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testGetFormatConf()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testInsertRule()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testDeleteRule()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testChangeRule()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testEnableRule()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testDisableRule()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testTransform()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testParse()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testRender()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function test_renderToken()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testRegisterRenderCallback()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testPopRenderCallback()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testGetSource()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testGetTokens()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testAddTokenReturnIdOnly()
    {
        $options = array('type' => 'someType', 'anotherOption' => 'value');
        $id = $this->obj->addToken('Test', $options, true);
        $this->assertEquals(0, $id);
    }

    public function testAddTokenReturnTokenNumberWithDelimiters()
    {
        /* the asserts below are failing because $id is a static variable inside addToken()
         * that persists with the same value even if you use different Text_Wiki objects
         * (although the comment on addToken says that the value of $id will reset to zero when
         * Text_Wiki object is created)
        $options = array('type' => 'someType', 'anotherOption' => 'value');
        $return1 = $this->obj->addToken('Test', $options);
        $return2 = $this->obj->addToken('Test', $options);
        $return3 = $this->obj->addToken('Test2', $options);
        $this->assertEquals('0', $return1);
        $this->assertEquals('1', $return2);
        $this->assertEquals('2', $return3);
        
        $tokens = array(0 => array(0 => 'Test', 1 => $options),
                        1 => array(0 => 'Test', 1 => $options),
                        2 => array(0 => 'Test2', 1 => $options));
        */
    }

    function addToken($rule, $options = array(), $id_only = false)
    {
        // increment the token ID number.  note that if you parse
        // multiple times with the same Text_Wiki object, the ID number
        // will not reset to zero.
        static $id;
        if (! isset($id)) {
            $id = 0;
        } else {
            $id ++;
        }

        // force the options to be an array
        settype($options, 'array');

        // add the token
        $this->tokens[$id] = array(
            0 => $rule,
            1 => $options
        );
        if (!isset($this->_countRulesTokens[$rule])) {
            $this->_countRulesTokens[$rule] = 1;
        } else {
            ++$this->_countRulesTokens[$rule];
        }

        // return a value
        if ($id_only) {
            // return the last token number
            return $id;
        } else {
            // return the token number with delimiters
            return $this->delim . $id . $this->delim;
        }
    }
    
    
    public function testSetToken()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testLoadParseObj()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testLoadRenderObj()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testLoadFormatObj()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testAddPath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testGetPath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testFindFile()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testFixPath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testError()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testIsError()
    {
        if (! class_exists('PEAR_Error')) {
            include_once 'PEAR.php';
        }
        
        $this->assertTrue($this->obj->isError(PEAR::throwError('Some error message')));
        $notPearErrorObject = new Text_Wiki;
        $this->assertFalse($this->obj->isError($notPearErrorObject));
    }
    
}

?>