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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the color attribute handler.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Format_Unit_Xml_Type_ColorTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadColor()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><color>#09aFAf</color>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
        );
        $this->assertEquals('#09aFAf', $attributes['color']);
    }

    public function testLoadStrangeColor()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><color type="strange"><b/>#012345<a/></color>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
        );
        $this->assertEquals('#012345', $attributes['color']);
    }

    public function testLoadMissingColor()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
        );
        $this->assertFalse(isset($attributes['color']));
    }

    public function testLoadDefault()
    {
        list($helper, $root_node, $type) = $this->getXmlType(
            'Horde_Kolab_Format_Stub_ColorDefault',
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $params = array();
        $type->load(
            $this->getElement($params), $attributes, $root_node, $helper, $params
        );
        $this->assertEquals('#abcdef', $attributes['color']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadInvalid()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><color>#09aFAfD</color>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,)
        );
    }

    public function testLoadInvalidRelaxed()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><color>#09aFAfD</color>c</kolab>',
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            )
        );
        $this->assertEquals('#09aFAfD', $attributes['color']);
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                null,
                array('color' => '#affcce'),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveColor()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><color>#FFFFFF</color></kolab>
',
            $this->saveToXml(
                null,
                array('color' => '#FFFFFF'),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><color type="strange">#000000<b/><a/></color>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><color type="strange"><b/>STRANGE<a/></color>c</kolab>',
                array('color' => '#000000'),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        list($helper, $root_node, $type) = $this->getXmlType(
            'Horde_Kolab_Format_Stub_ColorNotEmpty',
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $params = array();
        $type->save(
            $this->getElement($params), $attributes, $root_node, $helper, $params
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveInvalidColor()
    {
        $this->saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('color' => 'INVALID'),
            array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,)
        );
    }

    public function testSaveInvalidColorRelaxed()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><color>INVALID</color></kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
                array('color' => 'INVALID'),
                array(
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                    'relaxed' => true
                )
            )
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
        list($helper, $root_node, $type) = $this->getXmlType(
            'Horde_Kolab_Format_Stub_ColorNotEmpty',
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><color type="strange"><b/>STRANGE<a/></color>c</kolab>'
        );
        $attributes = array();
        $params = array();
        
        $this->assertInstanceOf(
            'DOMNode', 
            $type->save(
                $this->getElement($params), $attributes, $root_node, $helper, $params
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
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                    'relaxed' => true,
                )
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_Color';
    }
}
