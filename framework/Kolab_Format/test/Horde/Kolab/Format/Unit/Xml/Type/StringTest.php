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
extends Horde_Kolab_Format_TestCase
{
    public function testLoadString()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string>SOMETHING</string>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertEquals('SOMETHING', $attributes['string']);
    }

    public function testLoadStrangeString()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertEquals('STRANGE', $attributes['string']);
    }

    public function testLoadEmptyString()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><string></string></kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertSame('', $attributes['string']);
    }

    public function testLoadMissingString()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertFalse(isset($attributes['string']));
    }

    public function testLoadDefault()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => 'DEFAULT'
            )
        );
        $this->assertEquals('DEFAULT', $attributes['string']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testLoadNotEmpty()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            )
        );
    }

    public function testLoadNotEmptyRelaxed()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            )
        );
        $this->assertFalse(isset($attributes['string']));
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->_saveToReturn(
                null,
                array('string' => 'TEST'),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveXml()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><string>STRING</string></kolab>
',
            $this->_saveToXml(
                null,
                array('string' => 'STRING'),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange">NEW<b/><a/></string>c</kolab>
',
            $this->_saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>',
                array('string' => 'NEW'),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        $this->_saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(),
            array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY)
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->_saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>',
                array(),
                array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY)
            )
        );
    }

    public function testDeleteNode()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>
',
            $this->_saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>',
                array(),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        $this->assertFalse(
            $this->_saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
                array(),
                array(
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                    'relaxed' => true,
                )
            )
        );
    }

    private function _load($previous, $params = array())
    {
        list($type_params, $root_node, $type) = $this->_getString(
            $previous
        );
        $params = array_merge($type_params, $params);
        $attributes = array();
        $type->load('string', $attributes, $root_node, $params);
        return $attributes;
    }

    private function _saveToXml(
        $previous = null, $attributes = array(), $params = array()
    )
    {
        list($type_params, $root_node, $type) = $this->_getString($previous);
        $params = array_merge($type_params, $params);
        $type->save('string', $attributes, $root_node, $params);
        return (string)$params['helper'];
    }

    private function _saveToReturn(
        $previous = null, $attributes = array(), $params = array()
    )
    {
        list($type_params, $root_node, $type) = $this->_getString($previous);
        $params = array_merge($type_params, $params);
        return $type->save('string', $attributes, $root_node, $params);
    }

    private function _getString(
        $previous = null,
        $kolab_type = 'kolab',
        $version = '1.0'
    )
    {
        return $this->getXmlType(
            'Horde_Kolab_Format_Xml_Type_String',
            $previous,
            $kolab_type,
            $version
        );
    }
}
