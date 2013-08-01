<?php
/**
 * Test the integer attribute handler.
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
 * Test the integer attribute handler.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Format_Unit_Xml_Type_IntegerTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadInteger()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer>1</integer>c</kolab>'
        );
        $this->assertSame(1, $attributes['integer']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadStrangeInteger()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer type="strange"><b/>false<a/></integer>c</kolab>'
        );
    }

    public function testLoadMissingInteger()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertFalse(isset($attributes['integer']));
    }

    public function testLoadDefault()
    {
        $attributes = $this->loadWithClass(
            'Horde_Kolab_Format_Stub_IntegerDefault'
        );
        $this->assertSame(10, $attributes['integer']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testLoadNotEmpty()
    {
        $this->loadWithClass('Horde_Kolab_Format_Stub_IntegerNotEmpty');
    }

    public function testLoadNotEmptyRelaxed()
    {
        $attributes = $this->loadWithClass(
            'Horde_Kolab_Format_Stub_IntegerNotEmpty',
            null,
            array('relaxed' => true)
        );
        $this->assertFalse(isset($attributes['integer']));
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                null,
                array('integer' => 1)
            )
        );
    }

    public function testSaveInteger()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><integer>7</integer></kolab>
',
            $this->saveToXml(
                null,
                array('integer' => 7)
            )
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer type="strange">7<b/><a/></integer>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer type="strange"><b/>STRANGE<a/></integer>c</kolab>',
                array('integer' => 7)
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        $this->saveWithClass('Horde_Kolab_Format_Stub_IntegerNotEmpty');
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveInvalidInteger()
    {
        $this->saveWithClass(
            'Horde_Kolab_Format_Stub_IntegerNotEmpty',
            null,
            array(),
            array('integer' => 'INVALID')
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
       $this->assertInstanceOf(
            'DOMNode', 
            $this->saveWithClass(
                'Horde_Kolab_Format_Stub_IntegerNotEmpty',
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer type="strange"><b/>STRANGE<a/></integer>c</kolab>'
            )
       );
    }

    public function testSaveNotEmptyRelaxed()
    {
       $this->assertFalse(
            $this->saveWithClass(
                'Horde_Kolab_Format_Stub_IntegerNotEmpty',
                null,
                array('relaxed' => true)
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_Integer';
    }
}
