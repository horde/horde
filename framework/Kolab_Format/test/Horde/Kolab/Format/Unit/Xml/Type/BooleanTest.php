<?php
/**
 * Test the boolean attribute handler.
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
 * Test the boolean attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_BooleanTest
extends PHPUnit_Framework_TestCase
{
    public function testLoadTrue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean>true</boolean>c</kolab>'
        );
        $attributes = array();
        $result->load('boolean', $attributes, $rootNode);
        $this->assertTrue($attributes['boolean']);
    }

    public function testLoadFalse()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean>false</boolean>c</kolab>'
        );
        $attributes = array();
        $result->load('boolean', $attributes, $rootNode);
        $this->assertFalse($attributes['boolean']);
    }

    public function testLoadStrangeBoolean()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean type="strange"><b/>false<a/></boolean>c</kolab>'
        );
        $attributes = array();
        $result->load('boolean', $attributes, $rootNode);
        $this->assertFalse($attributes['boolean']);
    }

    public function testLoadMissingBoolean()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('boolean', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['boolean']));
    }

    public function testLoadDefault()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => true
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('boolean', $attributes, $rootNode);
        $this->assertTrue($attributes['boolean']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testLoadNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('boolean', $attributes, $rootNode);
    }

    public function testLoadNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('boolean', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['boolean']));
    }

    public function testSave()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean();
        $this->assertInstanceOf(
            'DOMNode',
            $result->save('boolean', array(), $rootNode)
        );
    }

    public function testSaveTrue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean();
        $result->save('boolean', array('boolean' => true), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><boolean>true</boolean></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveFalse()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean();
        $result->save('boolean', array('boolean' => false), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><boolean>false</boolean></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveOverwritesOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean type="strange"><b/>STRANGE<a/></boolean>c</kolab>'
        );
        $result->save('boolean', array('boolean' => false), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean type="strange">false<b/><a/></boolean>c</kolab>
',
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('boolean', array(), $rootNode);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveInvalidBoolean()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('boolean', array('boolean' => 'INVALID'), $rootNode);
    }

    public function testSaveNotEmptyWithOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean type="strange"><b/>STRANGE<a/></boolean>c</kolab>'
        );
        $this->assertInstanceOf(
            'DOMNode', 
            $result->save('boolean', array(), $rootNode)
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultBoolean(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertFalse($result->save('boolean', array(), $rootNode));
    }

    private function _getDefaultBoolean($params = array(), $previous = null)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $rootNode = $root->save();
        $result = new Horde_Kolab_Format_Xml_Type_Boolean($doc, $params);
        return array($doc, $rootNode, $result);
    }
}
