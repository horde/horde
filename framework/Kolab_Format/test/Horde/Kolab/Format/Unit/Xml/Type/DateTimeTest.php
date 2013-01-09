<?php
/**
 * Test the date-time attribute handler.
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
 * Test the date-time attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_DateTimeTest
extends Horde_Kolab_Format_TestCase
{
    public function testLoadDate()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime>2011-06-29</datetime>c</kolab>'
        );
        $this->assertTrue($attributes['datetime']['date-only']);
    }

    public function testLoadDateValue()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime>2011-06-29</datetime>c</kolab>'
        );
        $this->assertEquals(
            '2011-06-29T00:00:00+00:00',
            $attributes['datetime']['date']->format('c')
        );
    }

    public function testLoadStrangeDateTime()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime type="strange"><b/>2011-06-29<a/></datetime>c</kolab>'
        );
        $this->assertEquals(
            '2011-06-29T00:00:00+00:00',
            $attributes['datetime']['date']->format('c')
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadEmptyDateTime()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><datetime></datetime></kolab>'
        );
    }

    public function testLoadMissingDateTime()
    {
        $attributes = $this->load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertFalse(isset($attributes['datetime']));
    }

    public function testLoadDefault()
    {
        $attributes = $this->loadWithClass(
            'Horde_Kolab_Format_Stub_DateTimeDefault'
        );
        $this->assertInstanceOf('DateTime', $attributes['datetime']['date']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testLoadNotEmpty()
    {
        $this->loadWithClass('Horde_Kolab_Format_Stub_DateTimeNotEmpty');
    }

    public function testLoadNotEmptyRelaxed()
    {
         $attributes = $this->loadWithClass(
            'Horde_Kolab_Format_Stub_DateTimeNotEmpty',
            null,
            array('relaxed' => true)
        );
        $this->assertFalse(isset($attributes['datetime']));
    }

    public function testSaveDateTime()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><datetime>2011-06-29T11:11:11Z</datetime></kolab>
',
            $this->saveToXml(
                null,
                array(
                    'datetime' => array(
                        'date' => new DateTime(
                            '2011-06-29T11:11:11',
                            new DateTimeZone('UTC')
                        )
                    )
                )
            )
        );
    }

    public function testSaveTimeZone()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><datetime>2011-06-29T09:11:11Z</datetime></kolab>
',
            $this->saveToXml(
                null,
                array(
                    'datetime' => array(
                        'date' => new DateTime(
                            '2011-06-29T11:11:11',
                            new DateTimeZone('Europe/Berlin')
                        )
                    )
                )
            )
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime type="strange">2011-06-29<b/><a/></datetime>c</kolab>
',
            $this->saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime type="strange"><b/>STRANGE<a/></datetime>c</kolab>',
                array(
                    'datetime' => array(
                        'date' => new DateTime(
                            '2011-06-29T11:11:11',
                            new DateTimeZone('Europe/Berlin')
                        ),
                        'date-only' => true
                    )
                )
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        $this->saveWithClass('Horde_Kolab_Format_Stub_DateTimeNotEmpty');
    }

    public function testSaveNotEmptyWithOldValue()
    {
        $this->assertInstanceOf(
            'DOMNode', 
            $this->saveWithClass(
                'Horde_Kolab_Format_Stub_DateTimeNotEmpty',
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><datetime type="strange"><b/>STRANGE<a/></datetime>c</kolab>'
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
<kolab version="1.0" a="b"><datetime type="strange"><b/>STRANGE<a/></datetime>c</kolab>',
                array()
            )
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        $this->assertFalse(
            $this->saveWithClass(
                'Horde_Kolab_Format_Stub_DateTimeNotEmpty',
                null,
                array('relaxed' => true)
            )
        );
    }

    protected function getTypeClass()
    {
        return 'Horde_Kolab_Format_Xml_Type_DateTime';
    }
}
