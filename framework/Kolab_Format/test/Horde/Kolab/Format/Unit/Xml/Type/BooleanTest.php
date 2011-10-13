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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the boolean attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_BooleanTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadTrue()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean>true</boolean>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
        );
        $this->assertTrue($attributes['boolean']);
    }

    public function testLoadFalse()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean>false</boolean>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
        );
        $this->assertFalse($attributes['boolean']);
    }

    public function testLoadStrangeBoolean()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean type="strange"><b/>false<a/></boolean>c</kolab>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
        );
        $this->assertFalse($attributes['boolean']);
    }

    public function testLoadMissingBoolean()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
        );
        $this->assertFalse(isset($attributes['boolean']));
    }

    public function testLoadDefault()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => true
            )
        );
        $this->assertTrue($attributes['boolean']);
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
        $this->assertFalse(isset($attributes['boolean']));
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                null,
                array('boolean' => true),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveTrue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><boolean>true</boolean></kolab>
',
            $this->saveToXml(
                null,
                array('boolean' => true),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveFalse()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><boolean>false</boolean></kolab>
',
            $this->saveToXml(
                null,
                array('boolean' => false),
                array('value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING)
            )
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean type="strange">false<b/><a/></boolean>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean type="strange"><b/>STRANGE<a/></boolean>c</kolab>',
                array('boolean' => false),
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
            null,
            array(),
            array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY)
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveInvalidBoolean()
    {
        $this->saveToXml(
            null,
            array('boolean' => 'INVALID'),
            array('value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY)
        );
    }

    public function testSaveNotEmptyWithOldValue()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><boolean type="strange"><b/>STRANGE<a/></boolean>c</kolab>',
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
        return 'Horde_Kolab_Format_Xml_Type_Boolean';
    }
}
