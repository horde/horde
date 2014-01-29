<?php
/**
 * Test the handler for attributes with multiple values.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Test the handler for attributes with multiple values.
 *
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Unit_Xml_Type_MultipleTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadMultiple()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><string>a</string></kolab>'
        );
        $this->assertEquals(array('a'), $attributes['string']);
    }

    public function testLoadSeveralMultiple()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0">
<string>a</string>
<string>Ü</string>
<string>SOME<a/>STRANGE<b/>ONE</string>
<string></string>
</kolab>',
            array(
                'array' => array('type' => Horde_Kolab_Format_Xml::TYPE_STRING),
                'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            )
        );
        $this->assertEquals(array('a', 'Ü', 'SOME', ''), $attributes['string']);
    }

    public function testLoadDefault()
    {
        $params = array();
        list($helper, $root_node, $type) = $this->getXmlType(
            'Horde_Kolab_Format_Stub_MultipleDefault',
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $type->load(
            $this->getElement($params), $attributes, $root_node, $helper, $params
        );
        $this->assertEquals(array('X'), $attributes['string']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadNotEmpty()
    {
        $params = array();
        list($helper, $root_node, $type) = $this->getXmlType(
            'Horde_Kolab_Format_Stub_MultipleNotEmpty',
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $type->load(
            $this->getElement($params), $attributes, $root_node, $helper, $params
        );
    }

    public function testLoadNotEmptyRelaxed()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true
            )
        );
        $this->assertFalse(isset($attributes['string']));
    }

    public function testSave()
    {
        $this->assertEquals(
            array(),
            $this->saveToReturn(
                null,
                array('string' => array()),
                array(
                    'array' => array(
                        'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                    ),
                    'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                )
            )
        );
    }

    public function testSaveMultiple()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><string>a</string><string>B</string><string>Ü</string><string></string></kolab>
',
            $this->saveToXml(
                null,
                array('string' => array('a', 'B', 'Ü', '')),
                array(
                    'array' => array(
                        'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                    ),
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                )
            )
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c<string>a</string><string>B</string><string>Ü</string><string></string></kolab>
',
            $this->saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>',
                array('string' => array('a', 'B', 'Ü', '')),
                array(
                    'array' => array(
                        'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                    ),
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                )
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        $params = array();
        list($helper, $root_node, $type) = $this->getXmlType(
            'Horde_Kolab_Format_Stub_MultipleNotEmpty'
        );
        $type->save(
            $this->getElement($params), array(), $root_node, $helper, $params
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveInvalidMultiple()
    {
        $params = array('relaxed' => false);
        list($helper, $root_node, $type) = $this->getXmlType(
            'Horde_Kolab_Format_Xml_Type_Multiple_Boolean'
        );
        $type->save(
            'boolean', array('boolean' => array('INVALID')), $root_node, $helper, $params
        );
    }

    public function testSaveInvalidMultipleRelaxed()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><string>INVALID</string></kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
                array('string' => array('INVALID')),
                array(
                    'array' => array(
                        'type' => Horde_Kolab_Format_Xml::TYPE_BOOLEAN,
                    ),
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                    'relaxed' => true
                )
            )
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
        $params = array();
        list($helper, $root_node, $type) = $this->getXmlType(
            'Horde_Kolab_Format_Stub_MultipleNotEmpty',
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>'
        );
        $this->assertInstanceOf(
            'DOMNodeList', 
            $type->save(
                $this->getElement($params), array(), $root_node, $helper, $params
            )
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        $this->assertFalse(
            $this->saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
                array(),
                array(
                    'array' => array(
                        'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                    ),
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                    'relaxed' => true,
                )
            )
        );
    }

    public function testDeleteMultiple()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c<y/></kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c<y/><string>a</string></kolab>',
                array(),
                array(
                    'array' => array(
                        'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                    ),
                    'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
                )
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_Multiple_String';
    }
}
