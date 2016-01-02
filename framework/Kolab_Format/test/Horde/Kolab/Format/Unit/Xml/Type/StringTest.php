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
 * Test the string attribute handler.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
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
<kolab version="1.0" a="b"><string>SOMETHING</string>c</kolab>'
        );
        $this->assertEquals('SOMETHING', $attributes['string']);
    }

    public function testLoadStrangeString()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>'
        );
        $this->assertEquals('STRANGE', $attributes['string']);
    }

    public function testLoadEmptyString()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><string></string></kolab>'
        );
        $this->assertSame('', $attributes['string']);
    }

    public function testLoadMissingString()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertFalse(isset($attributes['string']));
    }

    public function testLoadDefault()
    {
        $attributes = $this->loadWithClass('Horde_Kolab_Format_Stub_StringDefault');
        $this->assertEquals('DEFAULT', $attributes['string']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testLoadNotEmpty()
    {
        $this->loadWithClass('Horde_Kolab_Format_Stub_StringNotEmpty');
    }

    public function testLoadNotEmptyRelaxed()
    {
        $attributes = $this->loadWithClass(
            'Horde_Kolab_Format_Stub_StringNotEmpty',
            null,
            array('relaxed' => true)
        );
        $this->assertFalse(isset($attributes['string']));
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                null,
                array('string' => 'TEST')
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
                array()
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
                array()
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        $this->saveWithClass('Horde_Kolab_Format_Stub_StringNotEmpty');
    }

    public function testSaveNotEmptyWithOldValue()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveWithClass(
                'Horde_Kolab_Format_Stub_StringNotEmpty',
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><string type="strange"><b/>STRANGE<a/></string>c</kolab>'
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
                array()
            )
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        $this->assertFalse(
            $this->saveWithClass(
                'Horde_Kolab_Format_Stub_StringNotEmpty',
                null,
                array('relaxed' => true)
            )
        );
    }

    public function testSaveDefaultRelaxed()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveWithClass(
                'Horde_Kolab_Format_Stub_StringDefault',
                null,
                array('relaxed' => true)
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_String';
    }
}
