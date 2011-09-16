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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the string attribute handler.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Format_Unit_Xml_Type_StringTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadString()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string>SOMETHING</string>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertEquals('SOMETHING', $attributes['string']);
    }

    public function testLoadStrangeString()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertEquals('STRANGE', $attributes['string']);
    }

    public function testLoadEmptyString()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><string></string></kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertSame('', $attributes['string']);
    }

    public function testLoadMissingString()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,)
        );
        $this->assertFalse(isset($attributes['string']));
    }

    public function testLoadDefault()
    {
        $attributes = $this->load(
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
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            )
        );
    }

    public function testLoadNotEmptyRelaxed()
    {
        $attributes = $this->load(
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
            $this->saveToReturn(
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
            $this->saveToXml(
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
            $this->saveToXml(
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
        $this->saveToXml(
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
            $this->saveToReturn(
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
            $this->saveToXml(
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

    public function testSaveDefaultRelaxed()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
                array(),
                array(
                    'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                    'default' => 'STRING',
                    'relaxed' => true,
                )
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_String';
    }
}
