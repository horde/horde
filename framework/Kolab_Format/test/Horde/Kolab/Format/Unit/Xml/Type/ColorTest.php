<?php
/**
 * Test the color attribute handler.
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
 * Test the color attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_ColorTest
extends PHPUnit_Framework_TestCase
{
    public function testLoadColor()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><color>#09aFAf</color>c</kolab>'
        );
        $attributes = array();
        $result->load('color', $attributes, $rootNode);
        $this->assertEquals('#09aFAf', $attributes['color']);
    }

    public function testLoadStrangeColor()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><color type="strange"><b/>#012345<a/></color>c</kolab>'
        );
        $attributes = array();
        $result->load('color', $attributes, $rootNode);
        $this->assertEquals('#012345', $attributes['color']);
    }

    public function testLoadMissingColor()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('color', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['color']));
    }

    public function testLoadDefault()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => '#abcdef'
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('color', $attributes, $rootNode);
        $this->assertEquals('#abcdef', $attributes['color']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadInvalid()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><color>#09aFAfD</color>c</kolab>'
        );
        $attributes = array();
        $result->load('color', $attributes, $rootNode);
    }

    public function testLoadInvalidRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><color>#09aFAfD</color>c</kolab>'
        );
        $attributes = array();
        $result->load('color', $attributes, $rootNode);
        $this->assertEquals('#09aFAfD', $attributes['color']);
    }

    public function testSave()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor();
        $this->assertInstanceOf(
            'DOMNode',
            $result->save('color', array(), $rootNode)
        );
    }

    public function testSaveColor()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor();
        $result->save('color', array('color' => '#FFFFFF'), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><color>#FFFFFF</color></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveOverwritesOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><color type="strange"><b/>STRANGE<a/></color>c</kolab>'
        );
        $result->save('color', array('color' => '#000000'), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><color type="strange">#000000<b/><a/></color>c</kolab>
',
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('color', array(), $rootNode);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveInvalidColor()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('color', array('color' => 'INVALID'), $rootNode);
    }

    public function testSaveInvalidColorRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('color', array('color' => 'INVALID'), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><color>INVALID</color></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><color type="strange"><b/>STRANGE<a/></color>c</kolab>'
        );
        $this->assertInstanceOf(
            'DOMNode', 
            $result->save('color', array(), $rootNode)
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultColor(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertFalse($result->save('color', array(), $rootNode));
    }

    private function _getDefaultColor($params = array(), $previous = null)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $rootNode = $root->save();
        $result = new Horde_Kolab_Format_Xml_Type_Color($doc, $params);
        return array($doc, $rootNode, $result);
    }
}
