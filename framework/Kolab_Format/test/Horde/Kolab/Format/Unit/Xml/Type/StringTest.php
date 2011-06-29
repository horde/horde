<?php
/**
 * Test the string attribute handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the string attribute handler.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Unit_Xml_Type_StringTest
extends PHPUnit_Framework_TestCase
{
    public function testLoadString()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string>SOMETHING</string>c</kolab>'
        );
        $attributes = array();
        $result->load('string', $attributes, $rootNode);
        $this->assertEquals('SOMETHING', $attributes['string']);
    }

    public function testLoadStrangeString()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>'
        );
        $attributes = array();
        $result->load('string', $attributes, $rootNode);
        $this->assertEquals('STRANGE', $attributes['string']);
    }

    public function testLoadEmptyString()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><string></string></kolab>'
        );
        $attributes = array();
        $result->load('string', $attributes, $rootNode);
        $this->assertSame('', $attributes['string']);
    }

    public function testLoadMissingString()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('string', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['string']));
    }

    public function testLoadDefault()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => 'DEFAULT'
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('string', $attributes, $rootNode);
        $this->assertEquals('DEFAULT', $attributes['string']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testLoadNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('string', $attributes, $rootNode);
    }

    public function testLoadNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('string', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['string']));
    }

    public function testSave()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString();
        $this->assertInstanceOf(
            'DOMNode', 
            $result->save('string', array(), $rootNode)
        );
    }

    public function testSaveXml()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString();
        $result->save('string', array('string' => 'STRING'), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><string>STRING</string></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveOverwritesOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>'
        );
        $result->save('string', array('string' => 'NEW'), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange">NEW<b/><a/></string>c</kolab>
',
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('string', array(), $rootNode);
    }

    public function testSaveNotEmptyWithOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>'
        );
        $this->assertInstanceOf(
            'DOMNode', 
            $result->save('string', array(), $rootNode)
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultString(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertFalse($result->save('string', array(), $rootNode));
    }

    private function _getDefaultString($params = array(), $previous = null)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $rootNode = $root->save();
        $result = new Horde_Kolab_Format_Xml_Type_String($doc, $params);
        return array($doc, $rootNode, $result);
    }
}
