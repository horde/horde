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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the handler for attributes with multiple values.
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
class Horde_Kolab_Format_Unit_Xml_Type_MultipleTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadMultiple()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><multiple>a</multiple></kolab>',
            array(
                'array' => array('type' => Horde_Kolab_Format_Xml::TYPE_STRING),
                'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            )
        );
        $this->assertEquals(array('a'), $attributes['multiple']);
    }

    public function testLoadSeveralMultiple()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0">
<multiple>a</multiple>
<multiple>Ü</multiple>
<multiple>SOME<a/>STRANGE<b/>ONE</multiple>
<multiple></multiple>
</kolab>',
            array(
                'array' => array('type' => Horde_Kolab_Format_Xml::TYPE_STRING),
                'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            )
        );
        $this->assertEquals(array('a', 'Ü', 'SOME', ''), $attributes['multiple']);
    }

    public function testLoadDefault()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => array('X'),
            )
        );
        $this->assertEquals(array('X'), $attributes['multiple']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadNotEmpty()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
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
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true
            )
        );
        $this->assertFalse(isset($attributes['multiple']));
    }

    public function testSave()
    {
        $this->assertEquals(
            array(),
            $this->saveToReturn(
                null,
                array('multiple' => array()),
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
<kolab version="1.0"><multiple>a</multiple><multiple>B</multiple><multiple>Ü</multiple><multiple></multiple></kolab>
',
            $this->saveToXml(
                null,
                array('multiple' => array('a', 'B', 'Ü', '')),
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
<kolab version="1.0" a="b">c<multiple>a</multiple><multiple>B</multiple><multiple>Ü</multiple><multiple></multiple></kolab>
',
            $this->saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><multiple type="strange"><b/>STRANGE<a/></multiple>c</kolab>',
                array('multiple' => array('a', 'B', 'Ü', '')),
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
        $this->saveToXml(
            null,
            array(),
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveInvalidMultiple()
    {
        $this->saveToXml(
            null,
            array('multiple' => array('INVALID')),
            array(
                'array' => array(
                    'type' => Horde_Kolab_Format_Xml::TYPE_BOOLEAN,
                ),
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            )
        );
    }

    public function testSaveInvalidMultipleRelaxed()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><multiple>INVALID</multiple></kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
                array('multiple' => array('INVALID')),
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
        $this->assertInstanceOf(
            'DOMNodeList', 
            $this->saveToReturn(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><multiple type="strange"><b/>STRANGE<a/></multiple>c</kolab>',
                array(),
                array(
                    'array' => array(
                        'type' => Horde_Kolab_Format_Xml::TYPE_STRING,
                    ),
                    'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                )
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
<kolab version="1.0" a="b"><multiple type="strange"><b/>STRANGE<a/></multiple>c<y/><multiple>a</multiple></kolab>',
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
        return 'Horde_Kolab_Format_Xml_Type_Multiple';
    }
}
