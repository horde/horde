<?php
/**
 * Test the modification-date attribute handler.
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
 * Test the modification-date attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_ModificationDateTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadModificationDate()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date>2011-06-28T08:42:11Z</modification-date>c</kolab>',
            array('element' => 'modification-date')
        );
        $this->assertInstanceOf('DateTime', $attributes['modification-date']);
    }

    public function testLoadModificationDateValue()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date>2011-06-28T08:42:11Z</modification-date>c</kolab>',
            array('element' => 'modification-date')
        );
        $this->assertEquals(
            1309250531,
            $attributes['modification-date']->format('U')
        );
    }

    public function testLoadStrangeModificationDate()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date type="strange"><b/>1970-01-01T00:00:00Z<a/></modification-date>c</kolab>',
            array('element' => 'modification-date')
        );
        $this->assertEquals(0, $attributes['modification-date']->format('U'));
    }

    public function testLoadMissingModificationDate()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>',
            array('element' => 'modification-date')
        );
        $this->assertInstanceOf('DateTime', $attributes['modification-date']);
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
        $this->assertRegexp(
            '#<modification-date>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z</modification-date>#',
            $this->saveToXml(
                null,
                array(),
                array('element' => 'modification-date')
            )
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertRegexp(
            '#<modification-date type="strange">\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z<b/><a/></modification-date>#',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date type="strange"><b/>1970-01-01T00:00:00Z<a/></modification-date>c</kolab>',
                array(),
                array('element' => 'modification-date')
            )
        );
    }


    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_ModificationDate';
    }
}
