<?php
/**
 * Test the creation-date attribute handler.
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
 * Test the creation-date attribute handler.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Format_Unit_Xml_Type_CreationDateTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadCreationDate()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date>2011-06-28T08:42:11Z</creation-date>c</kolab>',
            array('element' => 'creation-date')
        );
        $this->assertInstanceOf('DateTime', $attributes['creation-date']);
    }

    public function testLoadCreationDateValue()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date>2011-06-28T08:42:11Z</creation-date>c</kolab>',
            array('element' => 'creation-date')
        );
        $this->assertEquals(
            1309250531,
            $attributes['creation-date']->format('U')
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadInvalidCreationDateValue()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date>2011A-06-28T08:42:11Z</creation-date>c</kolab>',
            array('element' => 'creation-date')
        );
    }

    public function testLoadInvalidCreationDateValueRelaxed()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date>2011A-06-28T08:42:11Z</creation-date>c</kolab>',
            array(
                'relaxed' => true,
                'element' => 'creation-date',
            )
        );
        $this->assertFalse($attributes['creation-date']);
    }

    public function testLoadStrangeCreationDate()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>',
            array('element' => 'creation-date')
        );
        $this->assertEquals(0, $attributes['creation-date']->format('U'));
    }

    public function testLoadMissingCreationDate()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('element' => 'creation-date')
        );
        $this->assertInstanceOf('DateTime', $attributes['creation-date']);
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode',
            $this->saveToReturn()
        );
    }

    public function testSaveXml()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><creation-date>1970-01-01T00:00:00Z</creation-date></kolab>
',
            $this->saveToXml(
                null,
                array('creation-date' => new DateTime('1970-01-01T00:00:00Z')),
                array('element' => 'creation-date')
            )
        );
    }

    public function testSaveDoesNotTouchOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>',
                array('creation-date' => new DateTime('1970-01-01T00:00:00Z')),
                array('element' => 'creation-date')
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveFailsOverwritingOldValue()
    {
        $this->saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>',
            array('creation-date' => new DateTime('1971-01-01T00:00:00Z')),
            array('element' => 'creation-date')
        );
    }

    public function testSaveRelaxedOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange">1971-01-01T00:00:00Z<b/><a/></creation-date>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>',
                array('creation-date' => new DateTime('1971-01-01T00:00:00Z')),
                array(
                    'relaxed' => true,
                    'element' => 'creation-date'
                )
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_CreationDate';
    }
}
