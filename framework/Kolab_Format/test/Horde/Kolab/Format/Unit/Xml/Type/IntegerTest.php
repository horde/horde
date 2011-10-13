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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the integer attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_IntegerTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadInteger()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer>1</integer>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
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
<kolab version="1.0" a="b"><integer type="strange"><b/>false<a/></integer>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
        );
    }

    public function testLoadMissingInteger()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
        );
        $this->assertFalse(isset($attributes['integer']));
    }

    public function testLoadDefault()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => 5
            )
        );
        $this->assertSame(5, $attributes['integer']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testLoadNotEmpty()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,)
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
        $this->assertFalse(isset($attributes['integer']));
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                null,
                array('integer' => 1),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
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
                array('integer' => 7),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
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
                array('integer' => 7),
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

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveInvalidInteger()
    {
        $this->saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('integer' => 'INVALID'),
            array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY)
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
        $this->assertInstanceOf(
            'DOMNode', 
            $this->saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><integer type="strange"><b/>STRANGE<a/></integer>c</kolab>',
                array(),
                array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY)
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
        return 'Horde_Kolab_Format_Xml_Type_Integer';
    }
}
